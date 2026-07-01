<?php

namespace App\Modules\Examination\Services;

use App\Modules\Examination\Events\ExamCompleted;
use App\Modules\Examination\Events\ExamPublished;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use RuntimeException;

class ExaminationService
{
    // ── Exam types ─────────────────────────────────────────────────────────────

    public function createType(int $schoolId, array $data): ExamType
    {
        return ExamType::create(['school_id' => $schoolId, ...$data]);
    }

    public function updateType(ExamType $type, array $data): ExamType
    {
        $type->update($data);

        return $type->fresh();
    }

    // ── Exams ──────────────────────────────────────────────────────────────────

    public function create(int $schoolId, array $data): Exam
    {
        return Exam::create(['school_id' => $schoolId, ...$data]);
    }

    public function update(Exam $exam, array $data): Exam
    {
        if ($exam->status === 'completed') {
            throw new RuntimeException('Cannot modify a completed exam.');
        }
        $exam->update($data);

        return $exam->fresh();
    }

    public function publish(Exam $exam): Exam
    {
        if ($exam->status !== 'draft') {
            throw new RuntimeException('Only draft exams can be published.');
        }
        if ($exam->subjects()->count() === 0) {
            throw new RuntimeException('Cannot publish an exam with no subjects scheduled.');
        }

        $exam->update(['status' => 'published']);
        event(new ExamPublished($exam));

        return $exam->fresh();
    }

    public function complete(Exam $exam): Exam
    {
        if ($exam->status !== 'published') {
            throw new RuntimeException('Only published exams can be marked as completed.');
        }

        $exam->update(['status' => 'completed']);
        event(new ExamCompleted($exam));

        return $exam->fresh();
    }

    // ── Subjects ───────────────────────────────────────────────────────────────

    public function addSubject(Exam $exam, array $data): ExamSubject
    {
        if ($exam->status === 'completed') {
            throw new RuntimeException('Cannot add subjects to a completed exam.');
        }

        return ExamSubject::create([
            'school_id' => $exam->school_id,
            'exam_id'   => $exam->id,
            ...$data,
        ]);
    }

    public function removeSubject(ExamSubject $subject): void
    {
        if ($subject->exam->status === 'completed') {
            throw new RuntimeException('Cannot remove subjects from a completed exam.');
        }

        $subject->delete();
    }
}
