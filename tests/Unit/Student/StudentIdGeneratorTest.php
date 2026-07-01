<?php

namespace Tests\Unit\Student;

use App\Modules\School\Models\School;
use App\Modules\Student\Models\StudentIdConfig;
use App\Modules\Student\Services\StudentIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        $school        = School::create(['name' => 'Gen School', 'is_active' => true]);
        $this->schoolId = $school->id;

        StudentIdConfig::create([
            'school_id'       => $this->schoolId,
            'prefix'          => 'SMS',
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
        $generator = new StudentIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        $this->assertEquals("SMS/{$year}/0001", $id);
    }

    public function test_sequence_increments(): void
    {
        $generator = new StudentIdGeneratorService();

        $id1 = $generator->generate($this->schoolId);
        $id2 = $generator->generate($this->schoolId);
        $id3 = $generator->generate($this->schoolId);

        $year = now()->format('Y');
        $this->assertEquals("SMS/{$year}/0001", $id1);
        $this->assertEquals("SMS/{$year}/0002", $id2);
        $this->assertEquals("SMS/{$year}/0003", $id3);
    }

    public function test_ids_are_unique_under_concurrent_calls(): void
    {
        $generator = new StudentIdGeneratorService();
        $ids       = [];

        for ($i = 0; $i < 10; $i++) {
            $ids[] = $generator->generate($this->schoolId);
        }

        $this->assertCount(10, array_unique($ids), 'Duplicate IDs generated');
    }

    public function test_short_year_format(): void
    {
        StudentIdConfig::where('school_id', $this->schoolId)
            ->update(['year_format' => 'YY', 'last_sequence' => 0]);

        $generator = new StudentIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('y');

        $this->assertEquals("SMS/{$year}/0001", $id);
    }

    public function test_custom_separator_and_prefix(): void
    {
        StudentIdConfig::where('school_id', $this->schoolId)
            ->update(['prefix' => 'ABC', 'separator' => '-', 'last_sequence' => 0]);

        $generator = new StudentIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        $this->assertEquals("ABC-{$year}-0001", $id);
    }

    public function test_sequence_resets_on_new_year(): void
    {
        StudentIdConfig::where('school_id', $this->schoolId)
            ->update(['last_sequence' => 50, 'last_reset_year' => 2025]);

        $generator = new StudentIdGeneratorService();
        $id        = $generator->generate($this->schoolId);
        $year      = now()->format('Y');

        // Should reset to 0001 since last_reset_year (2025) != current year
        $this->assertEquals("SMS/{$year}/0001", $id);
    }
}
