<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Events\StudentWaitlisted;
use App\Modules\Student\Models\StudentWaitlist;
use App\Modules\Student\Repositories\WaitlistRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class WaitlistService extends BaseService
{
    public function __construct(WaitlistRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a new entry to the waitlist for a class/section/year.
     *
     * @param  array<string, mixed>  $data
     */
    public function addToWaitlist(int $schoolId, array $data): StudentWaitlist
    {
        /** @var WaitlistRepository $repo */
        $repo = $this->repository;

        $position = $repo->nextPosition(
            $schoolId,
            $data['class_id'],
            $data['section_id'] ?? null,
            $data['academic_year_id'],
        );

        $entry = StudentWaitlist::create(array_merge($data, [
            'school_id' => $schoolId,
            'position'  => $position,
            'status'    => 'waiting',
        ]));

        $this->repository->flush();
        event(new StudentWaitlisted($entry));

        return $entry;
    }

    /**
     * Mark a waitlist entry as admitted and notify.
     */
    public function markAdmitted(StudentWaitlist $entry): StudentWaitlist
    {
        $entry->update(['status' => 'admitted']);
        $this->repository->flush();

        return $entry->fresh();
    }

    /**
     * Cancel a waitlist entry and reorder remaining positions.
     */
    public function cancel(StudentWaitlist $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $entry->update(['status' => 'cancelled']);

            // Shift positions down for entries below the cancelled one
            StudentWaitlist::where('school_id', $entry->school_id)
                ->where('class_id', $entry->class_id)
                ->where('academic_year_id', $entry->academic_year_id)
                ->when($entry->section_id, fn ($q) => $q->where('section_id', $entry->section_id))
                ->where('status', 'waiting')
                ->where('position', '>', $entry->position)
                ->decrement('position');
        });

        $this->repository->flush();
    }
}
