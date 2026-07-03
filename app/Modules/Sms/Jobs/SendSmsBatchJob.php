<?php

namespace App\Modules\Sms\Jobs;

use App\Modules\School\Models\School;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Services\SmsBatchService;
use App\Modules\Student\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Every send request (1 recipient or 500) goes through this queued job —
 * same reasoning as IdCard's GenerateIdCardBatchJob: even though the stub
 * LogGateway makes no real network call today, a real provider later would,
 * and "text all guardians of a class" synchronously in one HTTP request is
 * exactly what stalls a web worker. QUEUE_CONNECTION=sync in tests runs this
 * inline, so no fake-queue plumbing is needed to test it.
 */
class SendSmsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $batchId) {}

    public function handle(SmsBatchService $service): void
    {
        $batch = SmsBatch::findOrFail($this->batchId);
        $batch->update(['status' => 'processing']);

        try {
            $school = School::findOrFail($batch->school_id);

            $targets = $batch->purpose === 'manual'
                ? $this->manualTargets($service, $batch)
                : $this->dueReminderTargets($service, $batch);

            foreach ($targets as $target) {
                $service->sendAndLog(
                    $school,
                    $batch->id,
                    $batch->purpose,
                    $target['student'],
                    $batch->requested_by,
                    $target['body'],
                );
            }

            $batch->update(['status' => 'completed', 'completed_at' => now()]);
        } catch (Throwable $e) {
            // Swallowed, not rethrown — same reasoning as IdCard's job: the batch
            // row's status/error_message is the client-facing signal, and rethrowing
            // would propagate into the HTTP request that dispatched it under the
            // sync queue driver used in tests.
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    /** @return Collection<int, array{student: Student, body: string}> */
    private function manualTargets(SmsBatchService $service, SmsBatch $batch): Collection
    {
        return $service->targetStudents($batch->school_id, $batch->scope, [
            'class_id' => $batch->class_id,
            'section_id' => $batch->section_id,
            'target_ids' => $batch->target_ids,
        ])->map(fn (Student $student) => ['student' => $student, 'body' => $batch->message_body]);
    }

    /** @return Collection<int, array{student: Student, body: string}> */
    private function dueReminderTargets(SmsBatchService $service, SmsBatch $batch): Collection
    {
        $dues = $service->studentsWithDues($batch->school_id, [
            'class_id' => $batch->class_id,
            'section_id' => $batch->section_id,
            'academic_year_id' => $batch->academic_year_id,
            'target_ids' => $batch->target_ids,
        ]);

        $students = Student::whereIn('id', $dues->pluck('student_id'))->get()->keyBy('id');

        return $dues->map(function ($due) use ($students) {
            $student = $students->get($due->student_id);

            if (! $student) {
                return null;
            }

            return [
                'student' => $student,
                'body' => __('sms.due_reminder', [
                    'student' => $student->name,
                    'amount' => number_format($due->total_due, 2),
                    'currency' => $due->currency,
                ]),
            ];
        })->filter()->values();
    }
}
