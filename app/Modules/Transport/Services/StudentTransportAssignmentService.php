<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Repositories\StudentTransportAssignmentRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class StudentTransportAssignmentService extends BaseService
{
    public function __construct(StudentTransportAssignmentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Seat a student on a route. Locks the route's current vehicle and counts
     * active riders inside the transaction so two concurrent assignments to the
     * last seat can't both pass — same shared-counter discipline as Library borrow
     * and Payment credit.
     *
     * @param  array<string, mixed>  $data
     */
    public function assign(int $schoolId, array $data): StudentTransportAssignment
    {
        $assignment = DB::transaction(function () use ($schoolId, $data): StudentTransportAssignment {
            $route = TransportRoute::forSchool($schoolId)->lockForUpdate()->findOrFail($data['transport_route_id']);

            // Interim state: a route between a breakdown and a replacement has no
            // operational vehicle. Block new riders until one is attached.
            if (! $route->current_vehicle_id) {
                throw new UnprocessableEntityHttpException('This route has no operational vehicle. Assign a vehicle before adding riders.');
            }

            $vehicle = TransportVehicle::forSchool($schoolId)->lockForUpdate()->findOrFail($route->current_vehicle_id);

            if ($vehicle->status !== 'in_service') {
                throw new UnprocessableEntityHttpException('This route has no operational vehicle. Assign a vehicle before adding riders.');
            }

            // One active assignment per student — a student rides one bus.
            $already = StudentTransportAssignment::forSchool($schoolId)
                ->where('student_id', $data['student_id'])->active()->exists();

            if ($already) {
                throw new UnprocessableEntityHttpException('This student already has an active transport assignment.');
            }

            $riders = StudentTransportAssignment::where('transport_route_id', $route->id)->active()->count();

            if ($riders >= $vehicle->capacity) {
                throw new UnprocessableEntityHttpException('The route vehicle is at full capacity.');
            }

            return StudentTransportAssignment::create([
                'school_id' => $schoolId,
                'student_id' => $data['student_id'],
                'transport_route_id' => $route->id,
                'pickup_point' => $data['pickup_point'] ?? null,
                'starts_on' => $data['starts_on'] ?? now()->toDateString(),
                'ends_on' => $data['ends_on'] ?? null,
                'status' => 'active',
            ]);
        });

        $this->repository->flush();

        return $assignment->fresh(['route', 'student']);
    }

    public function end(int $schoolId, int $id): StudentTransportAssignment
    {
        $assignment = StudentTransportAssignment::forSchool($schoolId)->findOrFail($id);

        if ($assignment->status === 'ended') {
            return $assignment->fresh(['route', 'student']);
        }

        $assignment->update([
            'status' => 'ended',
            'ends_on' => $assignment->ends_on ?? now()->toDateString(),
        ]);
        $this->repository->flush();

        return $assignment->fresh(['route', 'student']);
    }
}
