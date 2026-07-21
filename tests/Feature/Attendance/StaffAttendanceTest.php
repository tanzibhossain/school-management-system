<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\Staff\Models\Staff;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        foreach (range(0, 6) as $day) {
            SchoolOpeningHour::create([
                'school_id' => $this->school->id,
                'day_of_week' => $day,
                'is_open' => true,
                'open_time' => '08:00:00',
                'close_time' => '16:00:00',
            ]);
        }

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->staff = Staff::create([
            'school_id' => $this->school->id,
            'name' => 'RFID Teacher',
            'gender' => 'male',
            'rfid_number' => 'CARD-123',
        ]);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    // ── Punches ──────────────────────────────────────────────────────────────

    public function test_first_punch_clocks_in_and_second_punch_clocks_out(): void
    {
        $token = $this->token();

        // First punch creates the day's record → 201
        $this->withToken($token)
            ->postJson('/api/v2/attendance/staff/punch', ['rfid_number' => 'CARD-123'])
            ->assertCreated();

        $record = StaffAttendance::firstOrFail();
        $this->assertNotNull($record->check_in);
        $this->assertNull($record->check_out);

        // Second punch updates the same record → 200
        $this->withToken($token)
            ->postJson('/api/v2/attendance/staff/punch', ['rfid_number' => 'CARD-123'])
            ->assertOk();

        $record->refresh();
        $this->assertNotNull($record->check_out);
        $this->assertDatabaseCount('staff_attendances', 1); // same day = same row
    }

    public function test_unknown_rfid_card_rejected(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/attendance/staff/punch', ['rfid_number' => 'NO-SUCH-CARD'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rfid_number');
    }

    // ── Manual entry ─────────────────────────────────────────────────────────

    public function test_manual_clock_out_without_clock_in_is_flagged_incomplete(): void
    {
        $date = CarbonImmutable::now('UTC')->subDay()->toDateString();

        $this->withToken($this->token())
            ->postJson('/api/v2/attendance/staff/manual', [
                'staff_id' => $this->staff->id,
                'date' => $date,
                'check_out' => "{$date} 15:30:00",
            ])
            ->assertCreated()
            ->assertJsonFragment(['is_incomplete' => true]);
    }

    // ── Auto close ───────────────────────────────────────────────────────────

    public function test_auto_close_sets_checkout_to_closing_time_not_run_time(): void
    {
        $yesterday = CarbonImmutable::now('UTC')->subDay();

        StaffAttendance::create([
            'school_id' => $this->school->id,
            'staff_id' => $this->staff->id,
            'date' => $yesterday->toDateString(),
            'check_in' => $yesterday->setTime(8, 2),
            'source' => 'rfid',
        ]);

        $this->artisan('attendance:auto-close')->assertSuccessful();

        $record = StaffAttendance::firstOrFail();
        $this->assertTrue($record->is_auto_closed);
        // Closing time from school_opening_hours (16:00), never the job run time
        $this->assertSame(
            $yesterday->toDateString().' 16:00',
            $record->check_out->format('Y-m-d H:i'),
        );
    }

    public function test_auto_close_respects_off_policy(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v2/attendance/settings', ['auto_close_policy' => 'off'])
            ->assertOk();

        $yesterday = CarbonImmutable::now('UTC')->subDay();

        StaffAttendance::create([
            'school_id' => $this->school->id,
            'staff_id' => $this->staff->id,
            'date' => $yesterday->toDateString(),
            'check_in' => $yesterday->setTime(8, 0),
        ]);

        $this->artisan('attendance:auto-close')->assertSuccessful();

        $this->assertNull(StaffAttendance::firstOrFail()->check_out);
    }

    // ── Settings ─────────────────────────────────────────────────────────────

    public function test_settings_show_defaults_and_update(): void
    {
        $token = $this->token();

        $this->withToken($token)
            ->getJson('/api/v2/attendance/settings')
            ->assertOk()
            ->assertJsonFragment(['auto_close_policy' => 'closing_time', 'edit_window_days' => 7]);

        $this->withToken($token)
            ->putJson('/api/v2/attendance/settings', ['edit_window_days' => 14, 'auto_close_policy' => 'max_shift'])
            ->assertOk()
            ->assertJsonFragment(['edit_window_days' => 14, 'auto_close_policy' => 'max_shift']);
    }

    public function test_invalid_policy_rejected(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v2/attendance/settings', ['auto_close_policy' => 'sometimes'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('auto_close_policy');
    }
}
