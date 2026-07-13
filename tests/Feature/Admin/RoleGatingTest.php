<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — role gating. Finance + Reports = admin OR accountant;
 * everything else = admin only; dashboard = any authenticated staff.
 */
class RoleGatingTest extends TestCase
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

    public function test_admin_can_reach_all_areas(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        foreach (['/admin', '/admin/students', '/admin/invoices', '/admin/exams', '/admin/school', '/admin/reports/fee-collection'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_accountant_can_reach_finance_and_reports(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $this->get('/admin')->assertOk();                 // dashboard — any staff
        $this->get('/admin/invoices')->assertOk();         // finance
        $this->get('/admin/fee-items')->assertOk();        // finance
        $this->get('/admin/reports/fee-collection')->assertOk();
    }

    public function test_accountant_is_blocked_from_admin_only_areas(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        foreach (['/admin/students', '/admin/staff', '/admin/exams', '/admin/school', '/admin/announcements', '/admin/users'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_accountant_cannot_write_to_admin_only_area(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $this->post('/admin/students', ['name' => 'X'])->assertForbidden();
    }

    public function test_teacher_is_blocked_from_finance_and_admin_areas(): void
    {
        $this->actingAs($this->userWithRole('teacher'));
        $this->get('/admin')->assertOk();                  // dashboard is open to any staff
        $this->get('/admin/invoices')->assertForbidden();  // finance = admin/accountant only
        $this->get('/admin/students')->assertForbidden();  // admin only
    }
}
