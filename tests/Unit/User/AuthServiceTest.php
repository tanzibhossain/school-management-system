<?php

namespace Tests\Unit\User;

use App\Modules\User\Services\AuthService;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    public function test_lockout_thrown_after_max_attempts(): void
    {
        $key = 'login:lockout@test.com|1.2.3.4';
        RateLimiter::clear($key);

        // Simulate 3 hits (max attempts)
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 900);
        }

        $this->expectException(TooManyRequestsHttpException::class);

        // This should throw because tooManyAttempts returns true
        $service = new AuthService;

        // Access private method via reflection
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('enforceLockout');
        $method->setAccessible(true);
        $method->invoke($service, 'lockout@test.com', '1.2.3.4');
    }

    public function test_no_lockout_before_max_attempts(): void
    {
        $key = 'login:nolock@test.com|1.2.3.4';
        RateLimiter::clear($key);

        // Only 2 hits — should not lock
        for ($i = 0; $i < 2; $i++) {
            RateLimiter::hit($key, 900);
        }

        $service = new AuthService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('enforceLockout');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($service, 'nolock@test.com', '1.2.3.4');

        $this->assertTrue(true); // reached here = no exception
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login:lockout@test.com|1.2.3.4');
        RateLimiter::clear('login:nolock@test.com|1.2.3.4');
        parent::tearDown();
    }
}
