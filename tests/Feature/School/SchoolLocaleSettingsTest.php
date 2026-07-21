<?php

namespace Tests\Feature\School;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolLocaleSettingsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
    }

    private function token(): string
    {
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $admin->createToken('test', ['*'])->plainTextToken;
    }

    // ── Defaults ─────────────────────────────────────────────────────────────

    public function test_new_school_gets_global_neutral_defaults(): void
    {
        $this->school->refresh();

        $this->assertSame('USD', $this->school->currency);
        $this->assertSame('UTC', $this->school->timezone);
        $this->assertSame('en', $this->school->locale);
        $this->assertSame('jan_dec', $this->school->academic_year_pattern);
        $this->assertSame('Institution Code', $this->school->institution_code_label);
    }

    // ── Read ─────────────────────────────────────────────────────────────────

    public function test_public_profile_exposes_locale_settings_and_institution_code_label(): void
    {
        $this->school->update([
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'institution_code' => '123456',
            'institution_code_label' => 'EIIN',
        ]);

        $this->getJson('/api/v2/public/school')
            ->assertOk()
            ->assertJsonFragment([
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'institution_code' => '123456',
                'institution_code_label' => 'EIIN',
            ]);
    }

    // ── Write ────────────────────────────────────────────────────────────────

    public function test_updating_locale_settings_requires_auth(): void
    {
        $this->putJson('/api/v2/school', ['currency' => 'BDT'])
            ->assertUnauthorized();
    }

    public function test_admin_can_update_locale_settings(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v2/school', [
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'bn',
                'academic_year_pattern' => 'jan_dec',
                'country_code' => 'BD',
                'institution_code' => '654321',
                'institution_code_label' => 'EIIN',
            ])
            ->assertOk();

        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id,
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'bn',
            'country_code' => 'BD',
            'institution_code' => '654321',
            'institution_code_label' => 'EIIN',
        ]);
    }

    public function test_invalid_currency_and_timezone_are_rejected(): void
    {
        $token = $this->token();

        $this->withToken($token)
            ->putJson('/api/v2/school', ['currency' => 'TAKA'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');

        $this->withToken($token)
            ->putJson('/api/v2/school', ['timezone' => 'Dhaka/Nowhere'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('timezone');

        $this->withToken($token)
            ->putJson('/api/v2/school', ['academic_year_pattern' => 'dec_jan'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('academic_year_pattern');

        $this->withToken($token)
            ->putJson('/api/v2/school', ['country_code' => 'BGD'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('country_code');
    }
}
