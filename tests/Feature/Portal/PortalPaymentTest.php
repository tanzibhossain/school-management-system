<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Family-portal online payment — the guards that run before any gateway call
 * (availability + invoice ownership). The gateway round-trip itself is exercised
 * by the Payment module's own tests.
 */
class PortalPaymentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT', 'country_code' => 'BD',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
    }

    private function familyUser(): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('student');

        return $user;
    }

    public function test_initiate_blocked_when_online_payment_is_off(): void
    {
        PaymentConfig::create(['school_id' => $this->school->id, 'payment_mode' => 'offline']);

        $this->actingAs($this->familyUser());
        $this->post('/portal/pay/initiate', ['invoice_id' => 1, 'gateway' => 'bkash'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_initiate_rejects_an_invoice_not_owned_by_the_family(): void
    {
        // Online + bKash configured so we get past the availability check.
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'bkash_enabled' => true, 'bkash_app_key' => 'test-key',
        ]);

        $this->actingAs($this->familyUser());
        // No invoice with this id belongs to the (student-less) user → rejected
        // before any gateway call.
        $this->post('/portal/pay/initiate', ['invoice_id' => 999, 'gateway' => 'bkash'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
