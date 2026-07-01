<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\ClassRoutine;

class RoutineSchedulingService
{
    /**
     * Returns true if the requested room+period+day slot is already booked
     * for the given school (excluding the given routine ID on update).
     */
    public function isRoomConflict(
        int $schoolId,
        int $roomId,
        int $periodId,
        string $dayOfWeek,
        ?int $excludeId = null,
    ): bool {
        return ClassRoutine::where('school_id', $schoolId)
            ->where('room_id', $roomId)
            ->where('period_id', $periodId)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Returns true if the section already has a subject scheduled
     * in the same period+day (prevents double-booking a section).
     */
    public function isSectionConflict(
        int $schoolId,
        int $sectionId,
        int $periodId,
        string $dayOfWeek,
        ?int $excludeId = null,
    ): bool {
        return ClassRoutine::where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('period_id', $periodId)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Returns true if there is any conflict (room or section).
     */
    public function hasConflict(
        int $schoolId,
        int $roomId,
        int $sectionId,
        int $periodId,
        string $dayOfWeek,
        ?int $excludeId = null,
    ): bool {
        return $this->isRoomConflict($schoolId, $roomId, $periodId, $dayOfWeek, $excludeId)
            || $this->isSectionConflict($schoolId, $sectionId, $periodId, $dayOfWeek, $excludeId);
    }
}
