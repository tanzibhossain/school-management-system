<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment-config JSON API (mobile/admin) — now reads/writes the generic gateway
 * store, so it can configure every gateway and never leaks credentials.
 */
class PaymentConfigApiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT', 'country_code' => 'BD',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    public function test_update_configures_a_gateway_through_the_generic_store(): void
    {
        $this->withToken($this->token())->putJson('/api/v2/payment-config', [
            'payment_mode' => 'both',
            'gateways' => ['bkash' => [
                'enabled' => true, 'fee_pct' => 1.5,
                'credentials' => ['app_key' => 'k', 'app_secret' => 's', 'username' => 'u', 'password' => 'p'],
            ]],
        ])->assertOk();

        $config = PaymentConfig::where('school_id', $this->school->id)->first();
        $this->assertTrue($config->gatewayEnabled('bkash'));
        $this->assertSame('k', $config->credential('bkash', 'app_key'));
        $this->assertSame(1.5, $config->feePct('bkash'));
    }

    public function test_show_returns_generic_gateways_and_never_leaks_credentials(): void
    {
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => ['bkash' => ['enabled' => true, 'credentials' => [
                'app_key' => 'super-secret-key', 'app_secret' => 's', 'username' => 'u', 'password' => 'p',
            ]]],
        ]);

        $response = $this->withToken($this->token())->getJson('/api/v2/payment-config')->assertOk();

        $response->assertJsonPath('data.gateways.bkash.enabled', true)
            ->assertJsonPath('data.gateways.bkash.configured', true)
            ->assertDontSee('super-secret-key'); // credentials are never serialized
    }
}
