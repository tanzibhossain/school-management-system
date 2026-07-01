<?php

namespace Tests\Unit\School;

use App\Modules\School\Models\School;
use App\Modules\School\Repositories\SchoolRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SchoolRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_current_returns_school_with_relationships(): void
    {
        $school = School::create(['name' => 'Unit Test School', 'is_active' => true]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('tags')->andReturnSelf();
        $cache->shouldReceive('remember')->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $repo = new SchoolRepository($cache);
        $result = $repo->getCurrent();

        $this->assertInstanceOf(School::class, $result);
        $this->assertEquals('Unit Test School', $result->name);
        $this->assertTrue($result->relationLoaded('phones'));
        $this->assertTrue($result->relationLoaded('openingHours'));
    }

    public function test_get_current_returns_null_when_no_school_exists(): void
    {
        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('tags')->andReturnSelf();
        $cache->shouldReceive('remember')->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $repo = new SchoolRepository($cache);
        $result = $repo->getCurrent();

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
