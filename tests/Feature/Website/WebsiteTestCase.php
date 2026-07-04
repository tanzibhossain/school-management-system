<?php

namespace Tests\Feature\Website;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class WebsiteTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected School $school;

    protected AcademicYear $year;

    protected SchoolClass $class;

    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 5']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }
}
