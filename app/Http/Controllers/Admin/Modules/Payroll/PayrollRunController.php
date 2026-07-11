<?php

namespace App\Http\Controllers\Admin\Modules\Payroll;

use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index(): View
    {
        $runs = PayrollRun::where('school_id', app('current_school_id'))
            ->withCount('entries')
            ->orderByDesc('year')->orderByDesc('month')
            ->get();

        return view('admin.modules.payroll.runs.index', compact('runs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $run = $this->payroll->createRun($schoolId, $data['month'], $data['year'], $data['notes'] ?? null);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payroll.runs.show', $run->id)->with('status', 'Payroll run created.');
    }

    public function show(int $id): View
    {
        $run = PayrollRun::where('school_id', app('current_school_id'))
            ->with(['entries.staff:id,name,employee_id'])
            ->findOrFail($id);

        return view('admin.modules.payroll.runs.show', compact('run'));
    }

    public function process(int $id): RedirectResponse
    {
        return $this->transition($id, fn () => $this->payroll->processRun(app('current_school_id'), $id, request()->user()), 'Payroll processed.');
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->transition($id, fn () => $this->payroll->approveRun(app('current_school_id'), $id, request()->user()), 'Payroll approved.');
    }

    private function transition(int $id, callable $action, string $message): RedirectResponse
    {
        PayrollRun::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            $action();
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
    }
}
