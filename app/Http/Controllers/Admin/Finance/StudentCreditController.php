<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\CreditTransaction;
use App\Modules\Payment\Services\CreditService;
use App\Modules\Student\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class StudentCreditController extends Controller
{
    public function __construct(private readonly CreditService $credits) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $student = null;
        $balance = 0.0;
        $transactions = collect();

        if ($request->filled('student_id')) {
            $student = Student::where('school_id', $schoolId)->findOrFail($request->integer('student_id'));
            $balance = $this->credits->balance($schoolId, $student->id);
            $transactions = CreditTransaction::where('school_id', $schoolId)
                ->where('student_id', $student->id)->orderByDesc('id')->limit(200)->get();
        }

        return view('admin.finance.student-credit.index', [
            'students' => Student::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'student_id']),
            'student' => $student,
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'student_id' => ['required', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            if ($data['direction'] === 'credit') {
                $this->credits->credit($schoolId, $data['student_id'], (float) $data['amount'], 'manual', 0, (int) auth()->id(), $data['note'] ?? null);
            } else {
                $this->credits->debit($schoolId, $data['student_id'], (float) $data['amount'], 'manual', 0, (int) auth()->id(), $data['note'] ?? null);
            }
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.student-credit.index', ['student_id' => $data['student_id']])
            ->with('status', __('Credit Adjusted.'));
    }
}
