<?php

namespace App\Modules\Certificate\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestimonialTemplate extends Model
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

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /** @return BelongsTo<School, TestimonialTemplate> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<Testimonial> */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class, 'template_id');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeDefault($query): void
    {
        $query->where('is_default', true);
    }
}
