<?php

namespace App\Modules\Website\Services;

use App\Modules\Website\Models\Menu;
use App\Modules\Website\Models\MenuItem;
use Illuminate\Support\Facades\DB;

/**
 * Menu items are always saved as a full tree replace (matches the DevPlan's
 * own `PUT /menus/{id}/items` spec) rather than individual item CRUD — a
 * drag-to-reorder / nest-under-dropdown edit is naturally "here's the whole
 * new tree", not a sequence of single-row operations.
 */
class MenuService
{
    /**
     * @param  array<int, array<string, mixed>>  $items  One level of nesting via
     *                                                   an optional 'children' key on dropdown-type items.
     */
    public function replaceItems(Menu $menu, array $items): Menu
    {
        return DB::transaction(function () use ($menu, $items): Menu {
            $menu->allItems()->delete();

            foreach ($items as $index => $item) {
                $this->createItem($menu, $item, null, $index);
            }

            return $menu->fresh('items.children');
        });
    }

    /** @param array<string, mixed> $item */
    private function createItem(Menu $menu, array $item, ?int $parentId, int $index): void
    {
        $children = $item['children'] ?? [];
        unset($item['children']);

        $created = MenuItem::create(array_merge($item, [
            'school_id' => $menu->school_id,
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'sort_order' => $item['sort_order'] ?? $index,
        ]));

        foreach ($children as $childIndex => $child) {
            $this->createItem($menu, $child, $created->id, $childIndex);
        }
    }
}
