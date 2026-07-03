<?php

namespace App\Modules\Sms\Http\Controllers;

use App\Modules\Sms\Http\Requests\SendDueReminderRequest;
use App\Modules\Sms\Http\Requests\SendManualSmsRequest;
use App\Modules\Sms\Http\Resources\SmsBatchResource;
use App\Modules\Sms\Http\Resources\SmsLogResource;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Sms\Repositories\SmsBatchRepository;
use App\Modules\Sms\Services\SmsBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SmsController extends Controller
{
    public function __construct(
        private readonly SmsBatchService $service,
        private readonly SmsBatchRepository $batchRepository,
    ) {}

    /** POST /v2/sms/manual — free-text SMS to selected students' guardians. */
    public function sendManual(SendManualSmsRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $batch = $this->service->requestManual($schoolId, [
            'scope' => $data['scope'],
            'class_id' => $data['class_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'target_ids' => $data['target_ids'] ?? null,
        ], $data['body'], $request->user());

        // service->request*() returns a fresh()-refetched instance (see its docblock),
        // so wasRecentlyCreated is false and the auto-201 behavior other modules rely on
        // doesn't kick in here — set it explicitly since a batch genuinely was just created.
        return (new SmsBatchResource($batch))->response()->setStatusCode(201);
    }

    /** POST /v2/sms/due-reminders — auto-composed reminder to guardians of students with unpaid/partial invoices. */
    public function sendDueReminders(SendDueReminderRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $batch = $this->service->requestDueReminders($schoolId, [
            'scope' => $data['scope'] ?? 'all',
            'class_id' => $data['class_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'target_ids' => $data['target_ids'] ?? null,
        ], $request->user());

        return (new SmsBatchResource($batch))->response()->setStatusCode(201);
    }

    /** GET /v2/sms/batches — this school's send history. */
    public function index(): AnonymousResourceCollection
    {
        return SmsBatchResource::collection(
            $this->batchRepository->forSchool(app('current_school_id'))
        );
    }

    /** GET /v2/sms/batches/{id} — poll a batch's status and per-recipient logs. */
    public function show(int $id): SmsBatchResource
    {
        $batch = SmsBatch::forSchool(app('current_school_id'))->with('logs')->findOrFail($id);

        return new SmsBatchResource($batch);
    }

    /** POST /v2/sms/logs/{id}/resend — re-attempt one log entry verbatim (same body, freshly resolved phone). */
    public function resend(Request $request, int $id): SmsLogResource
    {
        $original = SmsLog::forSchool(app('current_school_id'))->findOrFail($id);

        return new SmsLogResource($this->service->resend($original, $request->user()));
    }
}
