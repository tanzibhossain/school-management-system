<?php

namespace App\Modules\Student\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferCertificateTemplate extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'template_body',
        'footer_text',
        'signatory_name',
        'signatory_designation',
        'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    /** @return BelongsTo<School, TransferCertificateTemplate> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<TransferCertificate> */
    public function certificates(): HasMany
    {
        return $this->hasMany(TransferCertificate::class, 'template_id');
    }

    /** @param Builder<TransferCertificateTemplate> $query */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
