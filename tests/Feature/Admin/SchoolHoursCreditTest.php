<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Payment\Models\StudentCredit;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — A4 school opening hours + A5 student credit ledger.
 */
class SchoolHoursCreditTest extends TestCase
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

    // ── A4 opening hours ─────────────────────────────────────────────────────────

    public function test_save_opening_hours(): void
    {
        $this->actingAs($this->admin);

        $days = [];
        for ($d = 0; $d <= 6; $d++) {
            $days[$d] = ['is_open' => $d === 5 ? '0' : '1', 'open_time' => '08:00', 'close_time' => '14:00'];
        }

        $this->put('/admin/school/hours', ['days' => $days])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('school_opening_hours', ['school_id' => $this->school->id, 'day_of_week' => 1, 'is_open' => true]);
        $this->assertDatabaseHas('school_opening_hours', ['school_id' => $this->school->id, 'day_of_week' => 5, 'is_open' => false]);
        $this->assertEquals(7, SchoolOpeningHour::where('school_id', $this->school->id)->count());
    }

    // ── A5 student credit ─────────────────────────────────────────────────────────

    public function test_credit_ledger_loads_and_adjusts(): void
    {
        $this->actingAs($this->admin);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'Payer', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active']);

        $this->get('/admin/student-credit')->assertOk();
        $this->get('/admin/student-credit?student_id='.$student->id)->assertOk();

        // credit 500
        $this->post('/admin/student-credit/adjust', ['student_id' => $student->id, 'direction' => 'credit', 'amount' => 500, 'note' => 'Advance'])
            ->assertRedirect();
        $this->assertEqualsWithDelta(500.0, (float) StudentCredit::where('student_id', $student->id)->value('balance'), 0.001);
        $this->assertDatabaseHas('credit_transactions', ['student_id' => $student->id, 'type' => 'credit', 'amount' => 500]);

        // debit 200 → balance 300
        $this->post('/admin/student-credit/adjust', ['student_id' => $student->id, 'direction' => 'debit', 'amount' => 200])->assertRedirect();
        $this->assertEqualsWithDelta(300.0, (float) StudentCredit::where('student_id', $student->id)->value('balance'), 0.001);
    }

    public function test_debit_beyond_balance_flashes_error(): void
    {
        $this->actingAs($this->admin);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'Broke', 'gender' => 'male', 'admission_number' => 'ADM-2', 'status' => 'active']);

        $this->post('/admin/student-credit/adjust', ['student_id' => $student->id, 'direction' => 'debit', 'amount' => 999])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_accountant_can_reach_student_credit(): void
    {
        $accountant = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $accountant->assignRole('accountant');
        $this->actingAs($accountant);

        $this->get('/admin/student-credit')->assertOk();  // Finance area
    }
}
