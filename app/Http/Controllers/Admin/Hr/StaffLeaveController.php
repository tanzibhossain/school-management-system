<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Services\StaffLeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\TransientToken;

class StaffLeaveController extends Controller
{
    public function __construct(private readonly StaffLeaveService $leave) {}

    public function index(Request $request): View
    {
        $query = StaffLeaveRequest::where('school_id', app('current_school_id'))
            ->with(['staff:id,name,employee_id', 'leaveType:id,name', 'approver:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.hr.staff-leave.index', [
            'requests' => $query->orderByDesc('id')->limit(500)->get(),
            'filters'  => $request->only('status'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->act($id, fn ($req, $user) => $this->leave->approve($req, $user), 'Leave approved.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        return $this->act($id, fn ($req, $user) => $this->leave->reject($req, $user, $reason), 'Leave rejected.');
    }

    public function cancel(int $id): RedirectResponse
    {
        return $this->act($id, fn ($req, $user) => $this->leave->cancel($req, $user), 'Leave cancelled.');
    }

    private function act(int $id, callable $action, string $message): RedirectResponse
    {
        $req = StaffLeaveRequest::where('school_id', app('current_school_id'))->findOrFail($id);
        $user = request()->user();
        $user->withAccessToken(new TransientToken()); // service gates on tokenCan('admin:*')

        try {
            $action($req, $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', $message);
    }
}
