<?php

namespace Tests\Unit\Loan;

use App\Modules\Loan\Services\LoanScheduleCalculator;
use InvalidArgumentException;
use Tests\TestCase;

class LoanScheduleCalculatorTest extends TestCase
{
    public function test_splits_amount_evenly_when_it_divides_cleanly(): void
    {
        $calculator = new LoanScheduleCalculator;

        $schedule = $calculator->calculateSchedule(12000, 12);

        $this->assertCount(12, $schedule);
        foreach ($schedule as $installment) {
            $this->assertEquals(1000.0, $installment['amount']);
        }
        $this->assertEquals(12000.0, array_sum(array_column($schedule, 'amount')));
    }

    public function test_last_installment_absorbs_rounding_remainder(): void
    {
        $calculator = new LoanScheduleCalculator;

        $schedule = $calculator->calculateSchedule(1000, 3);

        $this->assertCount(3, $schedule);
        $this->assertEquals(333.33, $schedule[0]['amount']);
        $this->assertEquals(333.33, $schedule[1]['amount']);
        $this->assertEquals(333.34, $schedule[2]['amount']);
        // The sum must always exactly equal the requested amount, however it divides.
        $this->assertEquals(1000.0, array_sum(array_column($schedule, 'amount')));
    }

    public function test_installment_numbers_are_sequential_starting_at_one(): void
    {
        $calculator = new LoanScheduleCalculator;

        $schedule = $calculator->calculateSchedule(5000, 5);

        $this->assertSame([1, 2, 3, 4, 5], array_column($schedule, 'installment_number'));
    }

    public function test_single_installment_returns_full_amount(): void
    {
        $calculator = new LoanScheduleCalculator;

        $schedule = $calculator->calculateSchedule(500, 1);

        $this->assertCount(1, $schedule);
        $this->assertEquals(500.0, $schedule[0]['amount']);
    }

    public function test_throws_on_non_positive_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LoanScheduleCalculator)->calculateSchedule(0, 5);
    }

    public function test_throws_on_zero_installment_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LoanScheduleCalculator)->calculateSchedule(1000, 0);
    }
}
