<?php

namespace Tests\Unit\Academic;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Repositories\AcademicRepository;
use App\Modules\School\Models\School;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AcademicRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_active_classes_returns_non_trashed_classes(): void
    {
        $school = School::create(['name' => 'Test School', 'is_active' => true]);

        SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 1', 'weight' => 1]);
        SchoolClass::create(['school_id' => $school->id, 'name' => 'Class 2', 'weight' => 2, 'is_trash' => true]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('tags')->andReturnSelf();
        $cache->shouldReceive('remember')->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $repo = new AcademicRepository($cache);
        $result = $repo->getActiveClasses($school->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Class 1', $result->first()->name);
    }

    public function test_get_current_year_returns_null_when_none_set(): void
    {
        $school = School::create(['name' => 'Test School', 'is_active' => true]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('tags')->andReturnSelf();
        $cache->shouldReceive('remember')->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $repo = new AcademicRepository($cache);
        $result = $repo->getCurrentYear($school->id);

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
