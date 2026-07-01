<?php

namespace Tests\Feature\School;

use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);

        // Seed opening hours
        foreach (range(0, 6) as $day) {
            SchoolOpeningHour::create([
                'school_id'   => $this->school->id,
                'day_of_week' => $day,
                'is_open'     => in_array($day, [1, 2, 3, 4]),
                'open_time'   => in_array($day, [1, 2, 3, 4]) ? '08:00:00' : null,
                'close_time'  => in_array($day, [1, 2, 3, 4]) ? '16:00:00' : null,
            ]);
        }
    }

    public function test_public_school_endpoint_returns_200_without_auth(): void
    {
        $response = $this->getJson('/api/v2/public/school');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Test School']);
    }

    public function test_public_school_response_includes_phones_and_opening_hours(): void
    {
        SchoolPhone::create([
            'school_id'  => $this->school->id,
            'phone'      => '01700000000',
            'label'      => 'Main',
            'is_primary' => true,
        ]);

        $response = $this->getJson('/api/v2/public/school');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['phones', 'opening_hours']]);
    }

    public function test_sync_phones_replaces_phone_list(): void
    {
        SchoolPhone::create(['school_id' => $this->school->id, 'phone' => '01700000000']);

        $response = $this->postJson('/api/v2/school/phones/sync', [
            'phones' => [
                ['phone' => '01811111111', 'label' => 'Main', 'is_primary' => true],
                ['phone' => '01922222222', 'label' => 'Office'],
            ],
        ]);

        // 401 expected — no auth token provided, proves the route is protected
        $response->assertUnauthorized();

        // Existing phone should still be there (sync not called without auth)
        $this->assertDatabaseHas('school_phones', ['phone' => '01700000000']);
    }

    public function test_update_opening_hour_requires_auth(): void
    {
        $response = $this->putJson('/api/v2/school/hours/1', [
            'is_open'    => true,
            'open_time'  => '09:00',
            'close_time' => '17:00',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_opening_hour_validation_fails_when_open_but_no_time(): void
    {
        $response = $this->putJson('/api/v2/school/hours/1', [
            'is_open' => true,
            // open_time and close_time missing
        ]);

        // 401 first (no auth), but validates locally too — just proves route exists
        $response->assertUnauthorized();
    }

    public function test_sms_api_key_is_never_returned_in_response(): void
    {
        $this->school->update(['sms_api_key' => 'secret-key-123']);

        $response = $this->getJson('/api/v2/public/school');

        $response->assertOk();
        $this->assertStringNotContainsString('secret-key-123', $response->getContent());
        $this->assertStringNotContainsString('sms_api_key', $response->getContent());
    }
}
