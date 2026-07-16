<?php

namespace App\Http\Controllers\Staff;

use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Services\StaffLeaveService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff self-service leave. A staff member applies for leave and can withdraw a
 * still-pending request; approval/rejection stays with the admin (HR).
 */
class LeaveController extends Controller
{
    public function __construct(private readonly StaffLeaveService $leave) {}

    public function index(): View
    {
        [$sid, $staff] = $this->context();

        $requests = $staff
            ? StaffLeaveRequest::where('school_id', $sid)->where('staff_id', $staff->id)
                ->with(['leaveType:id,name', 'approver:id,name'])->orderByDesc('id')->get()
            : collect();

        $leaveTypes = LeaveType::forSchool($sid)->active()->applicableTo('staff')->orderBy('name')->get(['id', 'name']);

        return view('staff.leave', compact('staff', 'requests', 'leaveTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$sid, $staff] = $this->context();
        abort_unless($staff, 403, 'No staff record is linked to your account.');

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', "exists:leave_types,id,school_id,{$sid}"],
            'from_date'     => ['required', 'date'],
            'to_date'       => ['required', 'date', 'after_or_equal:from_date'],
            'reason'        => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->leave->submit($sid, $staff, $data, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', 'Leave request submitted.');
    }

    public function cancel(int $id): RedirectResponse
    {
        [$sid, $staff] = $this->context();
        $req = StaffLeaveRequest::where('school_id', $sid)->where('staff_id', $staff?->id)->findOrFail($id);

        try {
            $this->leave->cancel($req, request()->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'This request can no longer be cancelled.');
        }

        return back()->with('status', 'Leave request cancelled.');
    }

    /** @return array{0:int,1:?Staff} */
    private function context(): array
    {
        $sid = app('current_school_id');

        return [$sid, Staff::where('school_id', $sid)->where('user_id', auth()->id())->first()];
    }
}
