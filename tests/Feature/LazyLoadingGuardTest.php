<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the N+1 guard (AppServiceProvider + App\Support\LazyLoadingReporter):
 * under the test suite, lazy-loading a relation on models from a multi-row
 * query must throw, and eager loading / single-row loads must not.
 */
class LazyLoadingGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'Guard School', 'is_active' => true]);

        foreach (['a', 'b'] as $n) {
            User::create([
                'school_id' => $school->id,
                'name' => "User {$n}",
                'email' => "{$n}@guard.test",
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);
        }
    }

    public function test_lazy_loading_across_a_multi_row_result_throws(): void
    {
        $this->expectException(LazyLoadingViolationException::class);

        User::query()->get()->each(fn (User $u) => $u->loginHistories);
    }

    public function test_eager_loading_does_not_throw(): void
    {
        $users = User::query()->with('loginHistories')->get();

        $this->assertCount(2, $users);
        $users->each(fn (User $u) => $this->assertCount(0, $u->loginHistories));
    }

    public function test_lazy_loading_a_single_fetched_model_is_allowed(): void
    {
        $user = User::query()->where('email', 'a@guard.test')->firstOrFail();

        $this->assertCount(0, $user->loginHistories);
    }
}
