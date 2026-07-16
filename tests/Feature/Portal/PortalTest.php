<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Family portal (student + guardian) — role gating, post-login routing, and
 * that a student sees their own record.
 */
class PortalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_student_and_parent_can_reach_portal(): void
    {
        $this->actingAs($this->userWithRole('student'));
        $this->get('/portal')->assertOk();
        $this->get('/portal/messages')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->userWithRole('parent'));
        $this->get('/portal')->assertOk();
        $this->get('/portal/messages')->assertOk();
    }

    public function test_staff_and_admin_cannot_reach_portal(): void
    {
        $this->actingAs($this->userWithRole('teacher'));
        $this->get('/portal')->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->userWithRole('admin'));
        $this->get('/portal')->assertForbidden();
    }

    public function test_guest_is_redirected_to_family_login(): void
    {
        $this->get('/portal')->assertRedirect('/login');
    }

    public function test_student_login_routes_to_portal(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true, 'password' => Hash::make('secret123')]);
        $student->assignRole('student');
        $this->post('/login', ['email' => $student->email, 'password' => 'secret123'])
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_student_sees_their_own_record(): void
    {
        $user = $this->userWithRole('student');
        Student::create([
            'school_id' => $this->school->id, 'user_id' => $user->id,
            'admission_number' => 'ADM-TEST-1', 'student_id' => 'STD-TEST-1',
            'name' => 'Portal Test Kid', 'gender' => 'male', 'status' => 'active',
        ]);

        $this->actingAs($user);
        $this->get('/portal')->assertOk()->assertSee('Portal Test Kid');
        $this->get('/portal/leave')->assertOk();
    }

    public function test_marksheet_requires_a_calculated_result(): void
    {
        $user = $this->userWithRole('student');
        Student::create([
            'school_id' => $this->school->id, 'user_id' => $user->id,
            'admission_number' => 'ADM-TEST-2', 'student_id' => 'STD-TEST-2',
            'name' => 'No Result Kid', 'gender' => 'female', 'status' => 'active',
        ]);

        $this->actingAs($user);
        $this->get('/portal/results/999/marksheet')->assertNotFound();
    }
}
