<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One staff member's calculated pay for one PayrollRun. */
class PayrollEntry extends Model
{
    protected $fillable = [
        'school_id',
        'payroll_run_id',
        'staff_id',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'breakdown',
        'payslip_path',
        'payslip_generated_at',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'breakdown' => 'array',
        'payslip_generated_at' => 'datetime',
    ];

    /** @return BelongsTo<PayrollRun, PayrollEntry> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /** @return BelongsTo<Staff, PayrollEntry> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @param Builder<PayrollEntry> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
