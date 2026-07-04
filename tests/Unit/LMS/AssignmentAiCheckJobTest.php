<?php

namespace Tests\Unit\LMS;

use App\Modules\LMS\Jobs\AssignmentAiCheckJob;
use Tests\TestCase;

/** Unit: "AI API timeout handled gracefully — job retries up to 3 times." */
class AssignmentAiCheckJobTest extends TestCase
{
    public function test_job_is_configured_to_retry_up_to_three_times_with_backoff(): void
    {
        $job = new AssignmentAiCheckJob(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff());
    }
}
