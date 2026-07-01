<?php

namespace Tests\Unit\Staff;

use App\Modules\School\Models\School;
use App\Modules\Staff\Models\StaffIdConfig;
use App\Modules\Staff\Services\StaffIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffIdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        $school         = School::create(['name' => 'Gen School', 'is_active' => true]);
        $this->schoolId = $school->id;

        StaffIdConfig::create([
            'school_id'       => $this->schoolId,
            'prefix'          => 'EMP',
            'include_year'    => true,
            'year_format'     => 'YYYY',
            'separator'       => '/',
            'sequence_length' => 4,
            'reset_yearly'    => true,
            'last_sequence'   => 0,
        ]);
    }

    public function test_generates_correct_format(): void
    {
        $generator = new StaffIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        $this->assertEquals("EMP/{$year}/0001", $id);
    }

    public function test_sequence_increments(): void
    {
        $generator = new StaffIdGeneratorService();

        $id1 = $generator->generate($this->schoolId);
        $id2 = $generator->generate($this->schoolId);
        $id3 = $generator->generate($this->schoolId);

        $year = now()->format('Y');
        $this->assertEquals("EMP/{$year}/0001", $id1);
        $this->assertEquals("EMP/{$year}/0002", $id2);
        $this->assertEquals("EMP/{$year}/0003", $id3);
    }

    public function test_ids_are_unique_under_concurrent_calls(): void
    {
        $generator = new StaffIdGeneratorService();
        $ids       = [];

        for ($i = 0; $i < 10; $i++) {
            $ids[] = $generator->generate($this->schoolId);
        }

        $this->assertCount(10, array_unique($ids), 'Duplicate employee IDs generated');
    }

    public function test_short_year_format(): void
    {
        StaffIdConfig::where('school_id', $this->schoolId)
            ->update(['year_format' => 'YY', 'last_sequence' => 0]);

        $generator = new StaffIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('y');

        $this->assertEquals("EMP/{$year}/0001", $id);
    }

    public function test_custom_separator_and_prefix(): void
    {
        StaffIdConfig::where('school_id', $this->schoolId)
            ->update(['prefix' => 'TCH', 'separator' => '-', 'last_sequence' => 0]);

        $generator = new StaffIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        $this->assertEquals("TCH-{$year}-0001", $id);
    }

    public function test_sequence_resets_on_new_year(): void
    {
        StaffIdConfig::where('school_id', $this->schoolId)
            ->update(['last_sequence' => 50, 'last_reset_year' => 2025]);

        $generator = new StaffIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        $this->assertEquals("EMP/{$year}/0001", $id);
    }
}
