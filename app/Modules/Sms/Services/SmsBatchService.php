<?php

namespace App\Modules\Sms\Services;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Sms\Gateways\SmsGatewayContract;
use App\Modules\Sms\Jobs\SendSmsBatchJob;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves batch targets (student list for manual sends, students-with-dues
 * for reminders), creates the SmsBatch + dispatches SendSmsBatchJob, and
 * provides the single sendAndLog() choke point both the job and resend()
 * use — one place that resolves a guardian's phone, computes segments/cost,
 * calls the gateway, and writes the SmsLog row.
 */
class SmsBatchService
{
    public function __construct(
        private readonly SmsSegmentCalculator $calculator,
        private readonly SmsGatewayContract $gateway,
    ) {}

    /**
     * @param  array{scope: string, class_id?: int|null, section_id?: int|null, target_ids?: array<int>|null}  $filters
     */
    public function requestManual(int $schoolId, array $filters, string $body, ?User $user): SmsBatch
    {
        $count = $this->targetStudents($schoolId, $filters['scope'], $filters)->count();

        $batch = SmsBatch::create([
            'school_id' => $schoolId,
            'purpose' => 'manual',
            'scope' => $filters['scope'],
            'class_id' => $filters['class_id'] ?? null,
            'section_id' => $filters['section_id'] ?? null,
            'target_ids' => $filters['target_ids'] ?? null,
            'message_body' => $body,
            'total_count' => $count,
            'status' => 'queued',
            'requested_by' => $user?->id,
        ]);

        SendSmsBatchJob::dispatch($batch->id);

        // Under QUEUE_CONNECTION=sync (tests, or any deployment without Horizon
        // running), dispatch() already ran the job to completion — refetch so the
        // returned instance reflects the final status, not the stale 'queued' one.
        return $batch->fresh(['logs']);
    }

    /**
     * @param  array{scope?: string, class_id?: int|null, section_id?: int|null, academic_year_id?: int|null, target_ids?: array<int>|null}  $filters
     */
    public function requestDueReminders(int $schoolId, array $filters, ?User $user): SmsBatch
    {
        $count = $this->studentsWithDues($schoolId, $filters)->count();

        $batch = SmsBatch::create([
            'school_id' => $schoolId,
            'purpose' => 'due_reminder',
            'scope' => $filters['scope'] ?? 'all',
            'class_id' => $filters['class_id'] ?? null,
            'section_id' => $filters['section_id'] ?? null,
            'academic_year_id' => $filters['academic_year_id'] ?? null,
            'target_ids' => $filters['target_ids'] ?? null,
            'total_count' => $count,
            'status' => 'queued',
            'requested_by' => $user?->id,
        ]);

        SendSmsBatchJob::dispatch($batch->id);

        return $batch->fresh(['logs']);
    }

    /**
     * Transport-module vehicle-swap alert. purpose = 'transport_alert', scope =
     * single (explicit rider student IDs). Unlike manual/due_reminder sends (which
     * reach the guardian only), this purpose delivers to BOTH the student and the
     * primary guardian — see sendAndLogDual().
     *
     * @param array<int> $studentIds
     */
    public function requestTransportAlert(int $schoolId, array $studentIds, string $body, ?int $actingUserId): SmsBatch
    {
        $batch = SmsBatch::create([
            'school_id' => $schoolId,
            'purpose' => 'transport_alert',
            'scope' => 'single',
            'target_ids' => array_values($studentIds),
            'message_body' => $body,
            'total_count' => count($studentIds),
            'status' => 'queued',
            'requested_by' => $actingUserId,
        ]);

        SendSmsBatchJob::dispatch($batch->id);

        return $batch->fresh(['logs']);
    }

    /**
     * @param  array{class_id?: int|null, section_id?: int|null, target_ids?: array<int>|null}  $filters
     * @return Collection<int, Student>
     */
    public function targetStudents(int $schoolId, string $scope, array $filters): Collection
    {
        $query = Student::where('school_id', $schoolId)->where('is_trash', false)->where('status', 'active');

        if ($scope === 'single') {
            $query->whereIn('id', $filters['target_ids'] ?? []);
        } elseif ($scope === 'class') {
            $classId = $filters['class_id'] ?? null;
            $sectionId = $filters['section_id'] ?? null;
            $query->whereHas('currentAcademic', function (Builder $q) use ($classId, $sectionId): void {
                $q->where('is_current', true)->where('class_id', $classId);
                if ($sectionId) {
                    $q->where('section_id', $sectionId);
                }
            });
        }

        return $query->get();
    }

    /**
     * Students with an unpaid/partial invoice, aggregated to one row each with
     * their total remaining due — built directly against Payment's tables
     * rather than depending on the Report module (each module stands alone).
     *
     * @param  array{class_id?: int|null, section_id?: int|null, academic_year_id?: int|null, target_ids?: array<int>|null}  $filters
     * @return Collection<int, object{student_id: int, total_due: float, currency: string}>
     */
    public function studentsWithDues(int $schoolId, array $filters): Collection
    {
        $query = DB::table('invoices')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('student_academics', function ($join): void {
                $join->on('student_academics.student_id', '=', 'invoices.student_id')
                    ->on('student_academics.academic_year_id', '=', 'invoices.academic_year_id');
            })
            ->where('invoices.school_id', $schoolId)
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->where('students.is_trash', false)
            ->where('students.status', 'active');

        if (! empty($filters['class_id'])) {
            $query->where('student_academics.class_id', $filters['class_id']);
        }
        if (! empty($filters['section_id'])) {
            $query->where('student_academics.section_id', $filters['section_id']);
        }
        if (! empty($filters['academic_year_id'])) {
            $query->where('invoices.academic_year_id', $filters['academic_year_id']);
        }
        if (! empty($filters['target_ids'])) {
            $query->whereIn('invoices.student_id', $filters['target_ids']);
        }

        $rows = $query->select([
            'invoices.student_id', 'invoices.amount_due', 'invoices.amount_paid',
            'invoices.credit_applied', 'invoices.currency',
        ])->get();

        return $rows->groupBy('student_id')->map(function (Collection $group) {
            $first = $group->first();
            $remaining = $group->sum(fn ($row) => (float) $row->amount_due - (float) $row->amount_paid - (float) $row->credit_applied);

            return (object) [
                'student_id' => (int) $first->student_id,
                'total_due' => round($remaining, 2),
                'currency' => $first->currency,
            ];
        })->values();
    }

    /**
     * The single choke point that resolves a guardian, computes segments/cost,
     * calls the gateway, and persists the SmsLog row — used by both
     * SendSmsBatchJob (per target) and resend() (single retry).
     */
    public function sendAndLog(
        School $school,
        int $batchId,
        string $purpose,
        Student $student,
        ?int $sentBy,
        string $body,
        ?int $resentFromId = null,
    ): SmsLog {
        $guardian = StudentGuardian::where('student_id', $student->id)->primary()->first()
            ?? StudentGuardian::where('student_id', $student->id)->first();

        $phone = $guardian?->phone;
        $segments = $this->calculator->calculate($body);
        $cost = $school->sms_cost_per_segment !== null
            ? round($segments['segment_count'] * (float) $school->sms_cost_per_segment, 4)
            : null;

        $base = [
            'school_id' => $school->id,
            'batch_id' => $batchId,
            'student_id' => $student->id,
            'guardian_id' => $guardian?->id,
            'body' => $body,
            'encoding' => $segments['encoding'],
            'segment_count' => $segments['segment_count'],
            'cost' => $cost,
            'purpose' => $purpose,
            'sent_by' => $sentBy,
            'resent_from_id' => $resentFromId,
            'sent_at' => now(),
        ];

        if (! $phone) {
            return SmsLog::create($base + [
                'recipient_phone' => null,
                'status' => 'failed',
                'error_message' => 'No guardian phone number on file.',
            ]);
        }

        $result = $this->gateway->send($school, $phone, $body);

        return SmsLog::create($base + [
            'recipient_phone' => $phone,
            'status' => $result->success ? 'sent' : 'failed',
            'error_message' => $result->errorMessage,
        ]);
    }

    /**
     * Dual-recipient send for transport alerts: reaches BOTH the primary guardian
     * and the student's own number (their linked User's phone). Emits one SmsLog
     * per distinct phone (guardian_id set for the guardian row, null for the
     * student row). The student number is best-effort — most students have no
     * User/phone, so a missing student number is simply skipped, not logged as a
     * failure. If neither number exists, one failed log records why.
     *
     * @return Collection<int, SmsLog>
     */
    public function sendAndLogDual(
        School $school,
        int $batchId,
        string $purpose,
        Student $student,
        ?int $sentBy,
        string $body,
    ): Collection {
        $guardian = StudentGuardian::where('student_id', $student->id)->primary()->first()
            ?? StudentGuardian::where('student_id', $student->id)->first();

        // phone => guardian_id (null for the student's own line); dedupes by phone.
        $recipients = [];
        if ($guardian?->phone) {
            $recipients[$guardian->phone] = $guardian->id;
        }
        $studentPhone = $student->user?->phone;
        if ($studentPhone && ! array_key_exists($studentPhone, $recipients)) {
            $recipients[$studentPhone] = null;
        }

        $segments = $this->calculator->calculate($body);
        $cost = $school->sms_cost_per_segment !== null
            ? round($segments['segment_count'] * (float) $school->sms_cost_per_segment, 4)
            : null;

        $base = [
            'school_id' => $school->id,
            'batch_id' => $batchId,
            'student_id' => $student->id,
            'body' => $body,
            'encoding' => $segments['encoding'],
            'segment_count' => $segments['segment_count'],
            'cost' => $cost,
            'purpose' => $purpose,
            'sent_by' => $sentBy,
            'sent_at' => now(),
        ];

        if ($recipients === []) {
            return collect([SmsLog::create($base + [
                'guardian_id' => null,
                'recipient_phone' => null,
                'status' => 'failed',
                'error_message' => 'No student or guardian phone number on file.',
            ])]);
        }

        $logs = collect();
        foreach ($recipients as $phone => $guardianId) {
            $result = $this->gateway->send($school, (string) $phone, $body);
            $logs->push(SmsLog::create($base + [
                'guardian_id' => $guardianId,
                'recipient_phone' => (string) $phone,
                'status' => $result->success ? 'sent' : 'failed',
                'error_message' => $result->errorMessage,
            ]));
        }

        return $logs;
    }

    /** Re-attempt a failed (or any) log entry verbatim — same body, freshly resolved phone. */
    public function resend(SmsLog $original, ?User $user): SmsLog
    {
        $school = School::findOrFail($original->school_id);
        $student = Student::findOrFail($original->student_id);

        return $this->sendAndLog(
            $school,
            $original->batch_id,
            $original->purpose,
            $student,
            $user?->id,
            $original->body,
            $original->id,
        );
    }
}
