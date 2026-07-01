<?php

namespace App\Modules\Examination\Models;

use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSeating extends Model
{
    // Laravel would pluralise to 'exam_seatings'; the migration created 'exam_seating'
    protected $table = 'exam_seating';

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_id',
        'hall_seat_id',
        'exam_roll',
        'group_id',
        'section_id',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /** @return BelongsTo<Exam, ExamSeating> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<Student, ExamSeating> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<ExamHallSeat, ExamSeating> */
    public function hallSeat(): BelongsTo
    {
        return $this->belongsTo(ExamHallSeat::class, 'hall_seat_id');
    }
}
