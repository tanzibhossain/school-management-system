<?php

namespace Tests\Unit\Sms;

use App\Modules\Sms\Services\SmsSegmentCalculator;
use Tests\TestCase;

class SmsSegmentCalculatorTest extends TestCase
{
    private SmsSegmentCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SmsSegmentCalculator();
    }

    public function test_empty_message_is_zero_segments(): void
    {
        $result = $this->calculator->calculate('');

        $this->assertSame('gsm7', $result['encoding']);
        $this->assertSame(0, $result['segment_count']);
    }

    public function test_plain_ascii_message_is_gsm7_single_segment(): void
    {
        $result = $this->calculator->calculate('Hello, this is a test message.');

        $this->assertSame('gsm7', $result['encoding']);
        $this->assertSame(1, $result['segment_count']);
    }

    public function test_gsm7_message_at_exactly_one_sixty_chars_is_one_segment(): void
    {
        $result = $this->calculator->calculate(str_repeat('a', 160));

        $this->assertSame('gsm7', $result['encoding']);
        $this->assertSame(1, $result['segment_count']);
    }

    public function test_gsm7_message_over_one_sixty_chars_splits_at_one_fifty_three_per_segment(): void
    {
        $result = $this->calculator->calculate(str_repeat('a', 161));

        $this->assertSame('gsm7', $result['encoding']);
        $this->assertSame(2, $result['segment_count']);

        $result = $this->calculator->calculate(str_repeat('a', 306)); // 2 * 153
        $this->assertSame(2, $result['segment_count']);

        $result = $this->calculator->calculate(str_repeat('a', 307)); // 2 * 153 + 1
        $this->assertSame(3, $result['segment_count']);
    }

    public function test_extended_gsm7_characters_cost_two_septets(): void
    {
        // 159 basic chars + 1 extended char ('{') = effective length 161 -> crosses
        // the 160 single-segment threshold even though only 160 real characters were typed.
        $message = str_repeat('a', 159).'{';

        $result = $this->calculator->calculate($message);

        $this->assertSame('gsm7', $result['encoding']);
        $this->assertSame(160, $result['char_count']);
        $this->assertSame(2, $result['segment_count']);
    }

    public function test_bangla_characters_force_unicode_encoding(): void
    {
        // "\u{0986}\u{0997}\u{09BE}\u{09AE}\u{09C0}" spells "আগামী" (Bengali) via
        // codepoint escapes rather than a raw literal — see SmsSegmentCalculator's
        // docblock on why this file avoids embedding real multi-byte UTF-8 bytes.
        $result = $this->calculator->calculate("Reminder: \u{0986}\u{0997}\u{09BE}\u{09AE}\u{09C0}");

        $this->assertSame('unicode', $result['encoding']);
        $this->assertGreaterThanOrEqual(1, $result['segment_count']);
    }

    public function test_unicode_message_at_exactly_seventy_chars_is_one_segment(): void
    {
        // U+0985 = BENGALI LETTER A ("অ"), written as an escape (see above).
        $result = $this->calculator->calculate(str_repeat("\u{0985}", 70));

        $this->assertSame('unicode', $result['encoding']);
        $this->assertSame(1, $result['segment_count']);
    }

    public function test_unicode_message_over_seventy_chars_splits_at_sixty_seven_per_segment(): void
    {
        $result = $this->calculator->calculate(str_repeat("\u{0985}", 71));

        $this->assertSame('unicode', $result['encoding']);
        $this->assertSame(2, $result['segment_count']);
    }

    public function test_a_single_emoji_forces_unicode(): void
    {
        // U+1F60A = SMILING FACE WITH SMILING EYES, written as an escape.
        $result = $this->calculator->calculate("Thank you \u{1F60A}");

        $this->assertSame('unicode', $result['encoding']);
    }
}
