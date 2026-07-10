<?php

namespace App\Modules\Payment\Models;

use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Invoice extends Model
{
    protected $fillable = [
        'school_id', 'invoice_number', 'student_id', 'academic_year_id', 'month',
        'amount_due', 'currency', 'amount_paid', 'credit_applied', 'status', 'due_date', 'note', 'issued_by',
    ];

    // Mirror DB-level default (services always set the school's currency explicitly)
    protected $attributes = ['currency' => 'USD'];

    protected $casts = [
        'amount_due'     => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'credit_applied' => 'decimal:2',
        'due_date'       => 'date',
        'month'          => 'integer',
    ];

    /** @return BelongsTo<Student, Invoice> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('is_reversed', false);
    }

    public function refunds(): HasManyThrough
    {
        return $this->hasManyThrough(Refund::class, Payment::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForStudent($query, int $studentId): void
    {
        $query->where('student_id', $studentId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeUnpaid($query): void
    {
        $query->whereIn('status', ['unpaid', 'partial']);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForYear($query, int $yearId): void
    {
        $query->where('academic_year_id', $yearId);
    }

    public function remainingAmount(): float
    {
        return round((float) $this->amount_due - (float) $this->amount_paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
