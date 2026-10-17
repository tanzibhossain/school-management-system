<?php

namespace App\Modules\Website\Models;

use App\Modules\Language\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    // locale: docs/modules/30-multilingual-content-plan.md — each Menu is one
    // full tree for one locale (Phase 3: MenuController/MenuService and the
    // public header composer are now locale-aware; see published() below).
    protected $fillable = ['school_id', 'name', 'locale'];

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<MenuItem, $this> */
    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /** @param Builder<Menu> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * The menu for one locale, falling back to the default locale's menu
     * when this locale has no menu built yet — same default-locale
     * fallback as PageRenderService::publishedLayoutFor(). Read-only: never
     * creates a row (see MenuController's own auto-vivifying lookup for the
     * admin editor, which — unlike public rendering — needs something to
     * edit even before a school has built that language's nav yet).
     * Returns null only when NEITHER locale has a menu at all, in which
     * case callers fall back to a hardcoded nav (see public/partials/
     * header.blade.php).
     */
    public static function published(int $schoolId, string $locale): ?self
    {
        $with = ['items.children.page', 'items.page'];

        $menu = static::forSchool($schoolId)->where('locale', $locale)->with($with)->first();
        if ($menu) {
            return $menu;
        }

        $default = Language::defaultCode();

        return $locale !== $default
            ? static::forSchool($schoolId)->where('locale', $default)->with($with)->first()
            : null;
    }
}
