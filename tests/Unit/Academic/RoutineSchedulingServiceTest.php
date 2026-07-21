<?php

namespace Tests\Unit\Academic;

use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\RoutineSchedulingService;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutineSchedulingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoutineSchedulingService $service;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RoutineSchedulingService;
        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
    }

    public function test_is_room_conflict_returns_false_when_no_routines_exist(): void
    {
        $result = $this->service->isRoomConflict($this->school->id, 1, 1, 'monday');

        $this->assertFalse($result);
    }

    public function test_is_room_conflict_returns_true_when_room_already_booked(): void
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Math']);
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'Room 101']);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '08:00', 'end_time' => '09:00']);

        ClassRoutine::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'day_of_week' => 'monday',
        ]);

        $result = $this->service->isRoomConflict($this->school->id, $room->id, $period->id, 'monday');

        $this->assertTrue($result);
    }

    public function test_is_room_conflict_excludes_given_id_on_update(): void
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Math']);
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'Room 101']);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '08:00', 'end_time' => '09:00']);

        $routine = ClassRoutine::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'day_of_week' => 'monday',
        ]);

        // Updating the same routine — should not conflict with itself
        $result = $this->service->isRoomConflict($this->school->id, $room->id, $period->id, 'monday', $routine->id);

        $this->assertFalse($result);
    }

    public function test_has_conflict_returns_false_for_different_day(): void
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Math']);
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'Room 101']);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '08:00', 'end_time' => '09:00']);

        ClassRoutine::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'day_of_week' => 'monday',
        ]);

        // Tuesday — different day, no conflict
        $result = $this->service->hasConflict($this->school->id, $room->id, $section->id, $period->id, 'tuesday');

        $this->assertFalse($result);
    }
}
