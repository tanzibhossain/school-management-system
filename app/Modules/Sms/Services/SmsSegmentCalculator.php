<?php

namespace App\Modules\Sms\Services;

/**
 * Pure, unit-tested class (same shape as Loan's LoanScheduleCalculator) —
 * no DB/queue access. Implements the GSM 03.38 encoding detection and the
 * real telecom segment thresholds CLAUDE.md calls out: GSM-7 is 160 chars
 * for a single-part message but only 153/part once concatenated across
 * multiple segments (7 septets reserved for the UDH), and Unicode (used the
 * moment ANY character falls outside the GSM-7 alphabet — e.g. Bangla) is
 * 70 single-part / 67/part concatenated (3 UCS-2 units reserved for the UDH).
 */
class SmsSegmentCalculator
{
    /**
     * GSM 03.38 basic character table — one septet each. Includes the Latin
     * letters/digits/punctuation plus the Western-European accented letters
     * and Greek capitals the standard defines at the basic-table code points.
     *
     * Written entirely with \u{...} codepoint escapes rather than raw
     * multi-byte literals — a source file with real UTF-8 bytes embedded in
     * a class constant is exactly the kind of thing that gets silently
     * re-encoded (Windows editor save, git autocrlf, Docker volume mount)
     * and then fails at PHP parse time with a misleading "constant
     * expression contains invalid operations" error. Pure-ASCII escapes
     * can't be corrupted in transit.
     */
    private const GSM_7_BASIC = "@\u{00A3}\$\u{00A5}\u{00E8}\u{00E9}\u{00F9}\u{00EC}\u{00F2}\u{00C7}\n\u{00D8}\u{00F8}\r\u{00C5}\u{00E5}\u{0394}_\u{03A6}\u{0393}\u{039B}\u{03A9}\u{03A0}\u{03A8}\u{03A3}\u{0398}\u{039E}\u{00C6}\u{00E6}\u{00DF}\u{00C9} !\"#\u{00A4}%&'()*+,-./0123456789:;<=>?\u{00A1}ABCDEFGHIJKLMNOPQRSTUVWXYZ\u{00C4}\u{00D6}\u{00D1}\u{00DC}\u{00A7}\u{00BF}abcdefghijklmnopqrstuvwxyz\u{00E4}\u{00F6}\u{00F1}\u{00FC}\u{00E0}";

    /**
     * GSM 03.38 extension table — reached via an escape character, so each
     * one costs 2 septets, not 1. (Omits the rare form-feed control char.)
     */
    private const GSM_7_EXTENDED = "^{}\\[~]|\u{20AC}";

    private const GSM_7_SINGLE_LIMIT = 160;

    private const GSM_7_MULTI_LIMIT = 153;

    private const UNICODE_SINGLE_LIMIT = 70;

    private const UNICODE_MULTI_LIMIT = 67;

    /**
     * @return array{encoding: string, char_count: int, segment_count: int}
     */
    public function calculate(string $message): array
    {
        $chars = mb_str_split($message);
        $isGsm7 = true;
        $effectiveLength = 0;

        foreach ($chars as $char) {
            if (str_contains(self::GSM_7_BASIC, $char)) {
                $effectiveLength += 1;
            } elseif (str_contains(self::GSM_7_EXTENDED, $char)) {
                $effectiveLength += 2;
            } else {
                $isGsm7 = false;
                break;
            }
        }

        $charCount = count($chars);

        if (! $isGsm7) {
            return [
                'encoding' => 'unicode',
                'char_count' => $charCount,
                'segment_count' => $this->segmentsFor($charCount, self::UNICODE_SINGLE_LIMIT, self::UNICODE_MULTI_LIMIT),
            ];
        }

        return [
            'encoding' => 'gsm7',
            'char_count' => $charCount,
            'segment_count' => $this->segmentsFor($effectiveLength, self::GSM_7_SINGLE_LIMIT, self::GSM_7_MULTI_LIMIT),
        ];
    }

    private function segmentsFor(int $length, int $singleLimit, int $multiLimit): int
    {
        if ($length === 0) {
            return 0;
        }
        if ($length <= $singleLimit) {
            return 1;
        }

        return (int) ceil($length / $multiLimit);
    }
}
