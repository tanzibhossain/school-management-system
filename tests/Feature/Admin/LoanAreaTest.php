<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Loan\Models\StaffLoan;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — HR › Staff loans (create, schedule on approval, reject/cancel).
 */
class LoanAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Staff $staff;

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
        $this->staff = Staff::create(['school_id' => $this->school->id, 'name' => 'Worker', 'gender' => 'male', 'employee_id' => 'EMP/1', 'status' => 'active']);
    }

    private function makeLoan(string $status = 'pending'): StaffLoan
    {
        return StaffLoan::create([
            'school_id' => $this->school->id, 'staff_id' => $this->staff->id,
            'requested_amount' => 12000, 'installment_count' => 12, 'reason' => 'Emergency',
            'start_date' => '2026-07-01', 'status' => $status, 'requested_by' => $this->admin->id,
        ]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/staff-loans')->assertOk();
    }

    public function test_create_loan_request(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/staff-loans', [
            'staff_id' => $this->staff->id, 'requested_amount' => 6000, 'installment_count' => 6,
            'reason' => 'Medical', 'start_date' => '2026-08-01',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('staff_loans', ['school_id' => $this->school->id, 'staff_id' => $this->staff->id, 'requested_amount' => 6000, 'status' => 'pending']);
    }

    public function test_approve_generates_schedule(): void
    {
        $this->actingAs($this->admin);
        $loan = $this->makeLoan();

        $this->patch("/admin/staff-loans/{$loan->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('staff_loans', ['id' => $loan->id, 'status' => 'approved']);
        $this->assertEquals(12, LoanSchedule::where('staff_loan_id', $loan->id)->count());
        $this->assertEqualsWithDelta(12000.0, (float) LoanSchedule::where('staff_loan_id', $loan->id)->sum('amount'), 0.001);
    }

    public function test_reject_loan(): void
    {
        $this->actingAs($this->admin);
        $loan = $this->makeLoan();

        $this->patch("/admin/staff-loans/{$loan->id}/reject", ['reason' => 'Outstanding advance'])->assertRedirect();
        $this->assertDatabaseHas('staff_loans', ['id' => $loan->id, 'status' => 'rejected']);
    }

    public function test_cannot_approve_non_pending(): void
    {
        $this->actingAs($this->admin);
        $loan = $this->makeLoan('approved');

        $this->patch("/admin/staff-loans/{$loan->id}/approve")->assertSessionHasErrors();
    }
}
