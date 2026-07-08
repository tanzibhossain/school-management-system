<?php

namespace App\Modules\Transport\Services;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\Transport as AcademicTransport;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Sms\Services\SmsBatchService;
use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Repositories\TransportRouteRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Owns the route lifecycle plus its two cross-module side effects: a route's fare
 * is mirrored into a Payment FeeItem (so riders get billed) and synced down to the
 * optional Academic transports row (so the public site shows the real price). The
 * vehicle swap marks the broken vehicle out of service, promotes a pool vehicle,
 * and SMS-notifies riders via the Sms module.
 */
class TransportRouteService extends BaseService
{
    public function __construct(
        TransportRouteRepository $repository,
        private readonly SmsBatchService $sms,
    ) {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function make(int $schoolId, array $data): TransportRoute
    {
        $route = DB::transaction(function () use ($schoolId, $data): TransportRoute {
            $route = TransportRoute::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'fare' => $data['fare'] ?? 0,
                'driver_id' => $data['driver_id'] ?? null,
                'academic_transport_id' => $data['academic_transport_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncFeeItem($route);
            $this->syncAcademicFare($route);

            return $route;
        });

        $this->repository->flush();

        return $route->fresh(['vehicle', 'driver']);
    }

    /** @param array<string, mixed> $data */
    public function modify(TransportRoute $route, array $data): TransportRoute
    {
        DB::transaction(function () use ($route, $data): void {
            $route->update($data);

            if (array_key_exists('fare', $data)) {
                $this->syncFeeItem($route);
                $this->syncAcademicFare($route);
            }
        });

        $this->repository->flush();

        return $route->fresh(['vehicle', 'driver']);
    }

    /**
     * Normal-ops vehicle assignment/detachment (not a breakdown). Frees the
     * previous vehicle back to the pool; the incoming one must be available and
     * large enough for the current riders.
     */
    public function setVehicle(int $schoolId, int $routeId, ?int $vehicleId): TransportRoute
    {
        $route = DB::transaction(function () use ($schoolId, $routeId, $vehicleId): TransportRoute {
            $route = TransportRoute::forSchool($schoolId)->lockForUpdate()->findOrFail($routeId);
            $old = $route->current_vehicle_id
                ? TransportVehicle::forSchool($schoolId)->lockForUpdate()->find($route->current_vehicle_id)
                : null;

            if ($vehicleId === null) {
                if ($old) {
                    $old->update(['status' => 'available']);
                }
                $route->update(['current_vehicle_id' => null]);

                return $route;
            }

            $new = TransportVehicle::forSchool($schoolId)->lockForUpdate()->findOrFail($vehicleId);

            if ($new->id !== ($old?->id) && $new->status !== 'available') {
                throw new UnprocessableEntityHttpException('That vehicle is not available.');
            }

            $riders = StudentTransportAssignment::where('transport_route_id', $route->id)->active()->count();
            if ($new->capacity < $riders) {
                throw new UnprocessableEntityHttpException("That vehicle seats {$new->capacity}, but the route has {$riders} active riders.");
            }

            if ($old && $old->id !== $new->id) {
                $old->update(['status' => 'available']);
            }
            $new->update(['status' => 'in_service']);
            $route->update(['current_vehicle_id' => $new->id]);

            return $route;
        });

        $this->repository->flush();

        return $route->fresh(['vehicle', 'driver']);
    }

    /**
     * Breakdown swap: current vehicle → out_of_service, an admin-chosen pool
     * vehicle → in_service, route repointed, riders SMS'd. Driver is untouched —
     * it stays with the route.
     */
    public function swapVehicle(int $schoolId, int $routeId, int $replacementId, ?int $actingUserId): TransportRoute
    {
        [$route, $new, $riderIds] = DB::transaction(function () use ($schoolId, $routeId, $replacementId): array {
            $route = TransportRoute::forSchool($schoolId)->lockForUpdate()->findOrFail($routeId);
            $new = TransportVehicle::forSchool($schoolId)->lockForUpdate()->findOrFail($replacementId);

            if ($new->status !== 'available') {
                throw new UnprocessableEntityHttpException('The replacement vehicle is not available in the pool.');
            }

            $riders = StudentTransportAssignment::where('transport_route_id', $route->id)->active();
            $riderCount = (clone $riders)->count();

            if ($new->capacity < $riderCount) {
                throw new UnprocessableEntityHttpException("The replacement seats {$new->capacity}, but the route has {$riderCount} active riders.");
            }

            if ($route->current_vehicle_id) {
                $old = TransportVehicle::forSchool($schoolId)->lockForUpdate()->find($route->current_vehicle_id);
                $old?->update(['status' => 'out_of_service']);
            }

            $new->update(['status' => 'in_service']);
            $route->update(['current_vehicle_id' => $new->id]);

            return [$route, $new, (clone $riders)->pluck('student_id')->all()];
        });

        // Notify AFTER commit — a gateway hiccup must never roll back the swap.
        $this->notifyRiders($schoolId, $route, $new, $riderIds, $actingUserId);

        $this->repository->flush();

        return $route->fresh(['vehicle', 'driver']);
    }

    /** @param array<int> $riderStudentIds */
    private function notifyRiders(int $schoolId, TransportRoute $route, TransportVehicle $vehicle, array $riderStudentIds, ?int $actingUserId): void
    {
        if ($riderStudentIds === []) {
            return;
        }

        $body = "Transport update for route \"{$route->name}\": your bus has changed to vehicle {$vehicle->registration_no}. Please contact the school office if you have questions.";

        $this->sms->requestTransportAlert($schoolId, $riderStudentIds, $body, $actingUserId);
    }

    /**
     * Create or update the route's linked transport FeeItem. Needs a current
     * academic year (fee items are year-scoped); if none exists yet, the fee link
     * is deferred and billing wires up once a year is set current.
     */
    private function syncFeeItem(TransportRoute $route): void
    {
        if ($route->fee_item_id) {
            FeeItem::whereKey($route->fee_item_id)->update([
                'amount' => $route->fare,
                'name' => "Transport: {$route->name}",
            ]);

            return;
        }

        $year = AcademicYear::where('school_id', $route->school_id)->current()->first();
        if (! $year) {
            return;
        }

        $category = FeeCategory::firstOrCreate(
            ['school_id' => $route->school_id, 'name' => 'Transport'],
            ['is_active' => true],
        );

        $feeItem = FeeItem::create([
            'school_id' => $route->school_id,
            'category_id' => $category->id,
            'academic_year_id' => $year->id,
            'class_id' => null,
            'transport_route_id' => $route->id,
            'name' => "Transport: {$route->name}",
            'amount' => $route->fare,
            'frequency' => 'monthly',
            'due_day' => null,
            'is_mandatory' => false,
            'is_active' => true,
        ]);

        $route->update(['fee_item_id' => $feeItem->id]);
    }

    /** One-way fare sync down to the optional Academic transports row (public display). */
    private function syncAcademicFare(TransportRoute $route): void
    {
        if (! $route->academic_transport_id) {
            return;
        }

        AcademicTransport::where('school_id', $route->school_id)
            ->whereKey($route->academic_transport_id)
            ->update(['fee' => $route->fare]);
    }
}
