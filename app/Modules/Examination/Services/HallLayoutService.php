<?php

namespace App\Modules\Examination\Services;

use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamSeating;
use RuntimeException;

class HallLayoutService
{
    /**
     * Generate (or regenerate) all seats for a hall from its layout_config.
     *
     * Example layout_config:
     * {
     *   "rows": 30,
     *   "sides": [
     *     { "label": "L", "seats_per_row": 4, "blocked_rows": [] },
     *     { "label": "R", "seats_per_row": 2, "blocked_rows": [23,24,25,26] }
     *   ]
     * }
     *
     * For the above:
     *   Rows  1-22 : L×4 + R×2 = 6 seats × 22 = 132
     *   Rows 23-26 : L×4 + R×0 = 4 seats ×  4 =  16  (door blocks R side)
     *   Rows 27-30 : L×4 + R×2 = 6 seats ×  4 =  24
     *   ─────────────────────────────────────────────
     *   Total                                   = 172
     *
     * @return int Number of seats created
     */
    public function generateSeats(ExamHall $hall): int
    {
        // Guard: refuse if active exam assignments exist for this hall
        $hasAssignments = ExamSeating::whereIn(
            'hall_seat_id',
            $hall->seats()->pluck('id')
        )->exists();

        if ($hasAssignments) {
            throw new RuntimeException(
                'Cannot regenerate seats: active seating assignments exist. Clear them first.'
            );
        }

        // Wipe existing seat definitions
        $hall->seats()->delete();

        $config = $hall->layout_config;
        $count = 0;

        for ($row = 1; $row <= $config['rows']; $row++) {
            foreach ($config['sides'] as $side) {
                $blockedRows = $side['blocked_rows'] ?? [];

                if (in_array($row, $blockedRows, true)) {
                    continue; // this entire side is blocked for this row
                }

                for ($pos = 1; $pos <= $side['seats_per_row']; $pos++) {
                    ExamHallSeat::create([
                        'hall_id' => $hall->id,
                        'row' => $row,
                        'side' => $side['label'],
                        'position' => $pos,
                        'label' => sprintf('R%02d-%s%d', $row, $side['label'], $pos),
                        'is_available' => true,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Toggle a single seat's availability (blocked by door, broken bench, projector leg, etc.).
     */
    public function toggleSeat(ExamHallSeat $seat): ExamHallSeat
    {
        $seat->update(['is_available' => ! $seat->is_available]);

        return $seat->fresh();
    }

    /**
     * Count available seats in a hall.
     */
    public function capacity(ExamHall $hall): int
    {
        return $hall->seats()->where('is_available', true)->count();
    }
}
