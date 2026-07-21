<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Payroll optional module (gating, components, staff salaries, runs).
 */
class PayrollModuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

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
    }

    private function enable(): void
    {
        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'payroll', 'is_enabled' => true]);
    }

    public function test_403_when_disabled(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/payroll/runs')->assertForbidden();
    }

    public function test_screens_load_when_enabled(): void
    {
        $this->actingAs($this->admin);
        $this->enable();
        foreach (['/admin/payroll/runs', '/admin/payroll/staff-salaries', '/admin/payroll/components'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_component_crud(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $this->post('/admin/payroll/components', ['name' => 'Basic', 'component_type' => 'earning'])->assertRedirect();
        $this->post('/admin/payroll/components', ['name' => 'PF', 'component_type' => 'deduction'])->assertRedirect();

        $this->assertDatabaseHas('salary_components', ['school_id' => $this->school->id, 'name' => 'Basic', 'component_type' => 'earning']);
        $this->assertDatabaseHas('salary_components', ['school_id' => $this->school->id, 'name' => 'PF', 'component_type' => 'deduction']);
    }

    public function test_set_staff_salary_values(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $staff = Staff::create(['school_id' => $this->school->id, 'name' => 'Teacher', 'gender' => 'female', 'employee_id' => 'EMP/1', 'status' => 'active']);
        $basic = SalaryComponent::create(['school_id' => $this->school->id, 'name' => 'Basic', 'component_type' => 'earning']);
        $pf = SalaryComponent::create(['school_id' => $this->school->id, 'name' => 'PF', 'component_type' => 'deduction']);

        $this->put("/admin/payroll/staff-salaries/{$staff->id}", [
            'amounts' => [$basic->id => 40000, $pf->id => 2000],
        ])->assertRedirect();

        $this->assertDatabaseHas('staff_salary_values', ['staff_id' => $staff->id, 'salary_component_id' => $basic->id, 'amount' => 40000]);
        $this->assertDatabaseHas('staff_salary_values', ['staff_id' => $staff->id, 'salary_component_id' => $pf->id, 'amount' => 2000]);
    }

    public function test_run_create_process_approve_flow(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $staff = Staff::create(['school_id' => $this->school->id, 'name' => 'Teacher', 'gender' => 'female', 'employee_id' => 'EMP/1', 'status' => 'active']);
        $basic = SalaryComponent::create(['school_id' => $this->school->id, 'name' => 'Basic', 'component_type' => 'earning']);
        $this->put("/admin/payroll/staff-salaries/{$staff->id}", ['amounts' => [$basic->id => 40000]])->assertRedirect();

        // create run
        $this->post('/admin/payroll/runs', ['month' => 6, 'year' => 2026])->assertRedirect();
        $run = PayrollRun::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals('draft', $run->status);

        // process → entry generated
        $this->patch("/admin/payroll/runs/{$run->id}/process")->assertRedirect();
        $this->assertDatabaseHas('payroll_entries', ['payroll_run_id' => $run->id, 'staff_id' => $staff->id, 'net_salary' => 40000]);

        // approve
        $this->patch("/admin/payroll/runs/{$run->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('payroll_runs', ['id' => $run->id, 'status' => 'approved']);
    }

    public function test_duplicate_run_period_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $this->post('/admin/payroll/runs', ['month' => 6, 'year' => 2026])->assertRedirect();
        $this->post('/admin/payroll/runs', ['month' => 6, 'year' => 2026])->assertRedirect()->assertSessionHas('error');

        $this->assertEquals(1, PayrollRun::where('school_id', $this->school->id)->count());
    }
}
