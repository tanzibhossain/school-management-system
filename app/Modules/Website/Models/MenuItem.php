<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const TYPES = ['page', 'external', 'dynamic', 'dropdown'];

    public const TARGETS = ['_self', '_blank'];

    protected $fillable = [
        'school_id',
        'menu_id',
        'parent_id',
        'label',
        'type',
        'page_id',
        'url',
        'dynamic_route',
        'target',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'target' => '_self',
        'sort_order' => 0,
    ];

    /** @return BelongsTo<Menu, MenuItem> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return BelongsTo<Page, MenuItem> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<MenuItem, MenuItem> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** One level of nesting only — a dropdown parent's direct children. @return HasMany<MenuItem> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
