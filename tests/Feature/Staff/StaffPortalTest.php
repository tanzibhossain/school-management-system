<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Staff / teacher portal — role gating and post-login routing.
 */
class StaffPortalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

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

    public function test_teacher_can_reach_staff_dashboard(): void
    {
        $this->actingAs($this->userWithRole('teacher'));
        $this->get('/staff')->assertOk();
        $this->get('/staff/profile')->assertOk();
        $this->get('/staff/notices')->assertOk();
        $this->get('/staff/attendance')->assertOk();
        $this->get('/staff/routine')->assertOk();
        $this->get('/staff/marks')->assertOk();
        $this->get('/staff/messages')->assertOk();
        $this->get('/staff/leave')->assertOk();
        $this->get('/staff/my-attendance')->assertOk();
    }

    public function test_admin_and_student_cannot_reach_staff_portal(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->get('/staff')->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->userWithRole('student'));
        $this->get('/staff')->assertForbidden();
    }

    public function test_guest_is_redirected_to_staff_login(): void
    {
        $this->get('/staff')->assertRedirect('/staff/login');
    }

    public function test_teacher_login_routes_to_staff_portal(): void
    {
        $teacher = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true, 'password' => Hash::make('secret123')]);
        $teacher->assignRole('teacher');
        $this->post('/staff/login', ['email' => $teacher->email, 'password' => 'secret123'])
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_can_clock_in(): void
    {
        $user = $this->userWithRole('teacher');
        $staff = Staff::create([
            'school_id' => $this->school->id, 'user_id' => $user->id,
            'employee_id' => 'EMP-CLK-1', 'name' => 'Clock Teacher', 'gender' => 'male', 'status' => 'active',
        ]);

        $this->actingAs($user);
        $this->post('/staff/my-attendance/punch')->assertRedirect();

        $this->assertDatabaseHas('staff_attendances', [
            'school_id' => $this->school->id, 'staff_id' => $staff->id,
        ]);
    }

    public function test_admin_login_routes_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true, 'password' => Hash::make('secret123')]);
        $admin->assignRole('admin');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
