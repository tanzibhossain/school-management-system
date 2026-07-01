<?php

namespace App\Modules\Examination\Services;

use App\Modules\Examination\Events\SeatingAssigned;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class SeatingService
{
    /**
     * Assign students to seats in a hall.
     *
     * Strategies:
     *   sequential          — all students sorted by roll_number
     *   interleave_group    — Science/Arts/Commerce round-robin within each row (left–right mixing)
     *   interleave_section  — Section A/B/C round-robin within each row
     *   anti_adjacency      — 2-D rotation: no two students from the same group sit
     *                         immediately in front, behind, left, or right of each other
     *
     * blank_every (optional):
     *   Leave 1 empty seat after every N students.
     *   e.g. blank_every=2 → student, student, [blank], student, student, [blank], …
     *
     * @return int Number of students seated
     */
    public function assign(
        Exam    $exam,
        int     $hallId,
        ?string $strategyOverride = null,
        ?int    $blankEvery       = null,
    ): int {
        // Clear any prior seating for this exam
        ExamSeating::where('exam_id', $exam->id)->delete();

        // Available seats ordered by row → side → position (L before R)
        $seats = ExamHallSeat::where('hall_id', $hallId)
            ->where('is_available', true)
            ->orderBy('row')
            ->orderBy('side')
            ->orderBy('position')
            ->get();

        $strategy = $strategyOverride ?? $exam->seating_strategy;

        $count = match ($strategy) {
            'anti_adjacency' => $this->assignAntiAdjacency($exam, $seats, $blankEvery),
            default          => $this->assignLinear($exam, $seats, $strategy, $blankEvery),
        };

        event(new SeatingAssigned($exam));

        return $count;
    }

    /**
     * Remove all seating assignments for an exam.
     */
    public function clear(Exam $exam): void
    {
        ExamSeating::where('exam_id', $exam->id)->delete();
    }

    // ── Linear strategies (sequential / interleave_group / interleave_section) ─

    /**
     * Order students according to strategy, then zip them sequentially into seats.
     * Optionally leave every (blank_every+1)th seat empty.
     */
    private function assignLinear(Exam $exam, Collection $seats, string $strategy, ?int $blankEvery): int
    {
        $students = $this->fetchStudents($exam, $strategy);

        // Filter out blank seats from the usable pool
        $usable = $blankEvery
            ? $seats->values()->filter(
                fn ($seat, int $idx) => ($idx % ($blankEvery + 1)) !== $blankEvery
            )->values()
            : $seats;

        if ($students->count() > $usable->count()) {
            throw new RuntimeException(sprintf(
                'Not enough seats (%d) for %d students in this hall.',
                $usable->count(),
                $students->count(),
            ));
        }

        foreach ($students as $i => $sa) {
            /** @var ExamHallSeat $seat */
            $seat = $usable[$i];

            ExamSeating::create([
                'school_id'    => $exam->school_id,
                'exam_id'      => $exam->id,
                'student_id'   => $sa->student_id,
                'hall_seat_id' => $seat->id,
                'exam_roll'    => str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'group_id'     => $sa->group_id,
                'section_id'   => $sa->section_id,
            ]);
        }

        return $students->count();
    }

    // ── Anti-adjacency strategy ────────────────────────────────────────────────

    /**
     * 2-D seating algorithm that ensures no two students from the same group
     * sit in immediately adjacent seats (front, back, left, right).
     *
     * How it works:
     *   Seats are processed row by row, side by side (L then R — each side is an
     *   independent segment separated by the aisle, so adjacency does not cross sides).
     *
     *   Within each segment the group assigned to position p is:
     *     group = (rowOffset + p) % numGroups
     *
     *   The rowOffset increments by 1 for each new hall row, so the column pattern
     *   shifts every row — preventing front/back same-group adjacency:
     *
     *     2 groups, 4 L-columns:
     *       Row 1 (offset 0):  S  A  S  A
     *       Row 2 (offset 1):  A  S  A  S   ← every column alternates ✓
     *       Row 3 (offset 0):  S  A  S  A
     *
     *   When a preferred group is exhausted, the algorithm falls back to the next
     *   available group (best-effort — perfect alternation may not be possible when
     *   group sizes differ greatly).
     *
     *   blank_every applies globally: every ($blankEvery+1)th physical seat slot
     *   is left empty.
     */
    private function assignAntiAdjacency(Exam $exam, Collection $seats, ?int $blankEvery): int
    {
        $rawStudents = $this->rawStudents($exam);
        $numStudents = $rawStudents->count();

        if ($numStudents === 0) {
            return 0;
        }

        // Capacity check (account for blank seats)
        $totalSeats  = $seats->count();
        $blankCount  = $blankEvery ? (int) floor($totalSeats / ($blankEvery + 1)) : 0;
        $usableCount = $totalSeats - $blankCount;

        if ($numStudents > $usableCount) {
            throw new RuntimeException(sprintf(
                'Not enough seats (%d usable%s) for %d students in this hall.',
                $usableCount,
                $blankEvery ? ", {$totalSeats} total with blank_every={$blankEvery}" : '',
                $numStudents,
            ));
        }

        // Build per-group queues sorted by roll_number
        $queues    = $rawStudents->groupBy('group_id')
            ->map(fn ($g) => $g->sortBy('roll_number')->values())
            ->values();
        $numGroups = $queues->count();

        // Pointers into each group's queue
        $pointers = array_fill(0, $numGroups, 0);

        // Group seats into (row, side) segments — preserving row/side/position order
        $segments = $seats->groupBy(fn ($s) => "{$s->row}-{$s->side}")->values();

        $assigned      = 0;
        $examRoll      = 1;
        $globalSeatIdx = 0; // tracks position across the entire hall (for blank_every)
        $prevRow       = null;
        $rowIndex      = -1;

        foreach ($segments as $segment) {
            // Advance row index when we move to a new hall row
            $hallRow = $segment->first()->row;
            if ($hallRow !== $prevRow) {
                $prevRow = $hallRow;
                $rowIndex++;
            }

            // Rotation offset for this row (shifts the group pattern every row)
            $rowOffset    = $numGroups > 1 ? ($rowIndex % $numGroups) : 0;
            $posInSegment = 0; // physical column index within this row-side segment

            foreach ($segment as $seat) {
                if ($assigned >= $numStudents) {
                    break 2;
                }

                // Should this physical slot be left blank?
                $isBlank = $blankEvery && ($globalSeatIdx % ($blankEvery + 1)) === $blankEvery;

                if (! $isBlank) {
                    // Preferred group for this seat position
                    $preferred = ($rowOffset + $posInSegment) % $numGroups;

                    // Draw from preferred group; fall back to next available
                    for ($attempt = 0; $attempt < $numGroups; $attempt++) {
                        $gIdx = ($preferred + $attempt) % $numGroups;

                        if ($pointers[$gIdx] < $queues[$gIdx]->count()) {
                            $sa = $queues[$gIdx][$pointers[$gIdx]++];

                            ExamSeating::create([
                                'school_id'    => $exam->school_id,
                                'exam_id'      => $exam->id,
                                'student_id'   => $sa->student_id,
                                'hall_seat_id' => $seat->id,
                                'exam_roll'    => str_pad((string) $examRoll, 4, '0', STR_PAD_LEFT),
                                'group_id'     => $sa->group_id,
                                'section_id'   => $sa->section_id,
                            ]);

                            $assigned++;
                            $examRoll++;
                            break;
                        }
                    }
                }

                $globalSeatIdx++;
                $posInSegment++;
            }
        }

        return $assigned;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Raw StudentAcademic rows scoped to the exam, ordered by roll_number.
     *
     * @return Collection<int, StudentAcademic>
     */
    private function rawStudents(Exam $exam): Collection
    {
        return StudentAcademic::where('school_id', $exam->school_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $exam->class_id)
            ->when($exam->section_id,  fn ($q) => $q->where('section_id', $exam->section_id))
            ->when($exam->group_id,    fn ($q) => $q->where('group_id', $exam->group_id))
            ->when($exam->version_id,  fn ($q) => $q->where('version_id', $exam->version_id))
            ->where('is_current', true)
            ->orderBy('roll_number')
            ->get();
    }

    /**
     * Pull StudentAcademic rows for the exam scope and apply the linear strategy ordering.
     *
     * @return Collection<int, StudentAcademic>
     */
    private function fetchStudents(Exam $exam, string $strategy): Collection
    {
        $students = $this->rawStudents($exam);

        return match ($strategy) {
            'interleave_group'   => $this->interleave($students, 'group_id'),
            'interleave_section' => $this->interleave($students, 'section_id'),
            default              => $students,
        };
    }

    /**
     * Round-robin interleave across groups/sections to prevent copying.
     *
     * Input (2 groups):
     *   [S1-Sci, S2-Sci] [A1-Arts, A2-Arts]
     *
     * Output (round-robin):
     *   S1-Sci, A1-Arts, S2-Sci, A2-Arts
     *
     * NOTE: groupBy() preserves original parent indices inside inner groups.
     * We must call values() on each inner group to re-index from 0 before
     * the position-based loop — otherwise students at high indices are silently dropped.
     *
     * @param  Collection<int, StudentAcademic>  $students
     * @return Collection<int, StudentAcademic>
     */
    private function interleave(Collection $students, string $key): Collection
    {
        $grouped = $students->groupBy($key)
            ->map(fn ($g) => $g->values())   // re-index inner groups to 0,1,2,...
            ->values();                       // re-index outer collection too

        $result = new Collection();
        $maxLen = $grouped->max(fn ($g) => $g->count());

        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($grouped as $group) {
                if ($group->has($i)) {
                    $result->push($group->get($i));
                }
            }
        }

        return $result;
    }
}
