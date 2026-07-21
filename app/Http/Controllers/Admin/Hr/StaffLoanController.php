<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Modules\Loan\Models\StaffLoan;
use App\Modules\Loan\Services\StaffLoanService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\TransientToken;

class StaffLoanController extends Controller
{
    public function __construct(private readonly StaffLoanService $loans) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $query = StaffLoan::where('school_id', $schoolId)->with('staff:id,name,employee_id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.hr.loans.index', [
            'loans' => $query->orderByDesc('id')->limit(500)->get(),
            'filters' => $request->only('status'),
            'staff' => Staff::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'employee_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'staff_id' => ['required', 'integer', "exists:staff,id,school_id,{$schoolId}"],
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:120'],
            'reason' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
        ], [], ['staff_id' => 'staff']);

        $staff = Staff::where('school_id', $schoolId)->findOrFail($data['staff_id']);
        $this->loans->submit($schoolId, $staff, $data, $request->user());

        return back()->with('status', 'Loan request created.');
    }

    public function show(int $id): View
    {
        $loan = StaffLoan::where('school_id', app('current_school_id'))
            ->with(['staff:id,name,employee_id', 'schedules' => fn ($q) => $q->orderBy('installment_number')])
            ->findOrFail($id);

        return view('admin.hr.loans.show', compact('loan'));
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->act($id, fn ($loan, $user) => $this->loans->approve($loan, $user), 'Loan approved.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        return $this->act($id, fn ($loan, $user) => $this->loans->reject($loan, $user, $reason), 'Loan rejected.');
    }

    public function cancel(int $id): RedirectResponse
    {
        return $this->act($id, fn ($loan, $user) => $this->loans->cancel($loan, $user), 'Loan cancelled.');
    }

    private function act(int $id, callable $action, string $message): RedirectResponse
    {
        $loan = StaffLoan::where('school_id', app('current_school_id'))->findOrFail($id);
        $user = request()->user();
        $user->withAccessToken(new TransientToken); // service gates on tokenCan('admin:*'/'accountant:*')

        try {
            $action($loan, $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', $message);
    }
}
