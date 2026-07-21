<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Setup › Class routine (periods/rooms + conflict-checked grid).
 */
class RoutineAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolClass $class;

    private Section $section;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths']);
        SubjectRelation::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'subject_id' => $this->subject->id]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/routine')->assertOk();
        $this->get('/admin/routine-setup')->assertOk();
    }

    public function test_period_and_room_crud(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/routine-setup/periods', ['name' => 'Period 1', 'start_time' => '09:00', 'end_time' => '09:45'])->assertRedirect();
        $this->assertDatabaseHas('routine_periods', ['school_id' => $this->school->id, 'name' => 'Period 1']);

        $this->post('/admin/routine-setup/rooms', ['name' => 'Room 101', 'capacity' => 40])->assertRedirect();
        $this->assertDatabaseHas('routine_rooms', ['school_id' => $this->school->id, 'name' => 'Room 101']);
    }

    public function test_add_routine_cell_and_conflict_guard(): void
    {
        $this->actingAs($this->admin);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '09:00', 'end_time' => '09:45']);
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'R1']);

        $payload = [
            'class_id' => $this->class->id, 'section_id' => $this->section->id, 'subject_id' => $this->subject->id,
            'room_id' => $room->id, 'period_id' => $period->id, 'day_of_week' => 'monday',
        ];

        $this->post('/admin/routine', $payload)->assertRedirect();
        $this->assertDatabaseHas('class_routines', ['section_id' => $this->section->id, 'period_id' => $period->id, 'day_of_week' => 'monday']);

        // same section+period+day again → conflict, not created
        $this->post('/admin/routine', $payload)->assertRedirect()->assertSessionHas('error');
        $this->assertEquals(1, ClassRoutine::where('section_id', $this->section->id)->count());
    }

    public function test_remove_routine_cell(): void
    {
        $this->actingAs($this->admin);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '09:00', 'end_time' => '09:45']);
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'R1']);
        $cell = ClassRoutine::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'section_id' => $this->section->id,
            'subject_id' => $this->subject->id, 'room_id' => $room->id, 'period_id' => $period->id, 'day_of_week' => 'monday',
        ]);

        $this->delete("/admin/routine/{$cell->id}")->assertRedirect();
        $this->assertDatabaseMissing('class_routines', ['id' => $cell->id]);
    }
}
