<?php

namespace App\Http\Controllers\Admin\Modules\Transport;

use App\Modules\Student\Models\Student;
use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Services\StudentTransportAssignmentService;
use App\Modules\Transport\Services\TransportRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RouteController extends Controller
{
    public function __construct(
        private readonly TransportRouteService $routes,
        private readonly StudentTransportAssignmentService $assignments,
    ) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $routes = TransportRoute::where('school_id', $schoolId)
            ->with(['vehicle:id,registration_no', 'driver:id,name'])
            ->withCount(['assignments as riders_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        return view('admin.modules.transport.routes.index', [
            'routes'  => $routes,
            'drivers' => TransportDriver::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->routes->make(app('current_school_id'), $this->validated($request));

        return back()->with('status', 'Route created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $route = TransportRoute::where('school_id', $schoolId)->findOrFail($id);
        $this->routes->modify($route, $this->validated($request));

        return back()->with('status', 'Route updated.');
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $route = TransportRoute::where('school_id', $schoolId)->with(['vehicle', 'driver'])->findOrFail($id);

        $riders = StudentTransportAssignment::where('school_id', $schoolId)
            ->where('transport_route_id', $route->id)->where('status', 'active')
            ->with('student:id,name,student_id')->get();

        return view('admin.modules.transport.routes.show', [
            'route'    => $route,
            'riders'   => $riders,
            'vehicles' => TransportVehicle::where('school_id', $schoolId)->where('status', 'available')->orderBy('registration_no')->get(['id', 'registration_no', 'capacity']),
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'student_id']),
        ]);
    }

    public function setVehicle(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        TransportRoute::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'vehicle_id' => ['nullable', 'integer', "exists:transport_vehicles,id,school_id,{$schoolId}"],
        ]);

        try {
            $this->routes->setVehicle($schoolId, $id, $data['vehicle_id'] ?? null);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Route vehicle updated.');
    }

    public function assign(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        TransportRoute::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'student_id'   => ['required', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'pickup_point' => ['nullable', 'string', 'max:150'],
            'starts_on'    => ['nullable', 'date'],
        ]);

        try {
            $this->assignments->assign($schoolId, [
                'student_id'         => $data['student_id'],
                'transport_route_id' => $id,
                'pickup_point'       => $data['pickup_point'] ?? null,
                'starts_on'          => $data['starts_on'] ?? null,
            ]);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Rider assigned.');
    }

    public function endAssignment(int $id, int $assignmentId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        TransportRoute::where('school_id', $schoolId)->findOrFail($id);
        $this->assignments->end($schoolId, $assignmentId);

        return back()->with('status', 'Rider removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $schoolId = app('current_school_id');

        return $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'fare'        => ['nullable', 'numeric', 'min:0'],
            'driver_id'   => ['nullable', 'integer', "exists:transport_drivers,id,school_id,{$schoolId}"],
        ]);
    }
}
