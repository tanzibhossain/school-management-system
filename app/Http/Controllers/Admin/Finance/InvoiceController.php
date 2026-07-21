<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\FeeItem\Models\FeeDiscount;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Services\InvoiceService;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $query = Invoice::where('school_id', $schoolId)->with('student:id,name,student_id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.finance.invoices.index', [
            'invoices' => $query->orderByDesc('id')->limit(500)->get(),
            'filters' => $request->only('status'),
            'years' => AcademicYear::where('school_id', $schoolId)->where('is_trash', false)->orderByDesc('year')->get(['id', 'year', 'is_current']),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'student_id']),
            'discounts' => FeeDiscount::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(int $id): View
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))
            ->with(['student:id,name,student_id', 'items', 'payments' => fn ($q) => $q->orderByDesc('id')])
            ->findOrFail($id);

        return view('admin.finance.invoices.show', compact('invoice'));
    }

    public function generateSingle(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'student_id' => ['required', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'discount_id' => ['nullable', 'integer', "exists:fee_discounts,id,school_id,{$schoolId}"],
            'due_date' => ['required', 'date'],
        ]);

        $classId = $this->currentClassId($schoolId, $data['student_id'], $data['academic_year_id']);

        try {
            $invoice = $this->invoices->generate(
                $schoolId, $data['academic_year_id'], $data['month'] ?? null,
                $data['student_id'], $classId, $data['discount_id'] ?? null,
                $data['due_date'], (int) auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.invoices.show', $invoice->id)->with('status', __('Invoice generated.'));
    }

    public function generateBulk(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'discount_id' => ['nullable', 'integer', "exists:fee_discounts,id,school_id,{$schoolId}"],
            'due_date' => ['required', 'date'],
        ]);

        // students currently in this class for the chosen year (students has no class_id)
        $studentIds = StudentAcademic::where('school_id', $schoolId)
            ->where('class_id', $data['class_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('is_current', true)
            ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
            ->pluck('student_id');

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($studentIds as $studentId) {
            $open = Invoice::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('month', $data['month'] ?? null)
                ->whereIn('status', ['unpaid', 'partial'])
                ->exists();

            if ($open) {
                $skipped++;

                continue;
            }

            try {
                $this->invoices->generate(
                    $schoolId, $data['academic_year_id'], $data['month'] ?? null,
                    $studentId, $data['class_id'], $data['discount_id'] ?? null,
                    $data['due_date'], (int) auth()->id(),
                );
                $generated++;
            } catch (RuntimeException) {
                $failed++;
            }
        }

        $msg = "Bulk generation complete — {$generated} generated, {$skipped} skipped".($failed ? ", {$failed} failed (no fee items)" : '').'.';

        return back()->with($failed && ! $generated ? 'error' : 'status', $msg);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($id);
        $note = $request->validate(['note' => ['required', 'string', 'max:255']])['note'];

        try {
            $this->invoices->cancel($invoice, $note, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Invoice cancelled.'));
    }

    public function waive(Request $request, int $id): RedirectResponse
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($id);
        $note = $request->validate(['note' => ['required', 'string', 'max:255']])['note'];

        try {
            $this->invoices->waive($invoice, $note, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Invoice waived.'));
    }

    private function currentClassId(int $schoolId, int $studentId, int $yearId): ?int
    {
        return StudentAcademic::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId)
            ->where('is_current', true)
            ->value('class_id');
    }
}
