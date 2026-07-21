<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\GenerateInvoiceRequest;
use App\Modules\Payment\Http\Resources\InvoiceCollection;
use App\Modules\Payment\Http\Resources\InvoiceResource;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Repositories\InvoiceRepository;
use App\Modules\Payment\Services\InvoiceService;
use App\Modules\Student\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly InvoiceRepository $repository,
    ) {}

    public function index(Request $request): InvoiceCollection
    {
        $invoices = $this->repository->paginate(
            app('current_school_id'),
            $request->only(['student_id', 'academic_year_id', 'month', 'status']),
        );

        return new InvoiceCollection($invoices);
    }

    public function show(int $id): InvoiceResource
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))
            ->with(['items', 'payments'])
            ->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    public function generate(GenerateInvoiceRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();
        $issuedBy = $request->user()->id;

        // Bulk generation (by class)
        if (isset($data['class_id']) && ! isset($data['student_id'])) {
            $result = $this->service->generateBulk(
                $schoolId,
                $data['academic_year_id'],
                $data['month'] ?? null,
                $data['class_id'],
                $data['discount_id'] ?? null,
                $data['due_date'],
                $issuedBy,
            );

            return response()->json([
                'message' => "{$result['generated']} invoice(s) generated, {$result['skipped']} skipped.",
                'generated' => $result['generated'],
                'skipped' => $result['skipped'],
            ]);
        }

        // Single student generation
        $student = Student::where('school_id', $schoolId)
            ->with('currentAcademic')
            ->findOrFail($data['student_id']);

        $invoice = $this->service->generate(
            $schoolId,
            $data['academic_year_id'],
            $data['month'] ?? null,
            $student->id,
            $student->currentAcademic?->class_id,
            $data['discount_id'] ?? null,
            $data['due_date'],
            $issuedBy,
        );

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function cancel(Request $request, int $id): InvoiceResource|JsonResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:500']]);
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            return new InvoiceResource($this->service->cancel($invoice, $request->note, $request->user()->id));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function waive(Request $request, int $id): InvoiceResource|JsonResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:500']]);
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            return new InvoiceResource($this->service->waive($invoice, $request->note, $request->user()->id));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Portal: student's own invoices. */
    public function myInvoices(Request $request): InvoiceCollection
    {
        $user = $request->user();
        $student = Student::where('school_id', app('current_school_id'))
            ->where('user_id', $user->id)
            ->firstOrFail();

        $invoices = $this->repository->unpaidForStudent(app('current_school_id'), $student->id);

        return new InvoiceCollection($invoices);
    }
}
