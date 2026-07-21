<?php

namespace App\Modules\FeeItem\Services;

use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Repositories\FeeItemRepository;
use App\Services\BaseService;

class FeeItemService extends BaseService
{
    public function __construct(FeeItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new fee item scoped to the school.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(int $schoolId, array $data): FeeItem
    {
        $item = FeeItem::create(array_merge($data, ['school_id' => $schoolId]));
        $this->repository->flush();

        return $item->load('category');
    }

    /**
     * Update a fee item and flush cache.
     *
     * @param  array<string, mixed>  $data
     */
    public function modify(FeeItem $item, array $data): FeeItem
    {
        $item->update($data);
        $this->repository->flush();

        return $item->fresh(['category']);
    }

    /**
     * Soft-disable a fee item (never hard-delete — Payment history depends on it).
     */
    public function deactivate(FeeItem $item): void
    {
        $item->update(['is_active' => false]);
        $this->repository->flush();
    }

    /**
     * Copy all active fee items from one academic year to another.
     * Skips duplicates (same name + class_id + category in target year).
     *
     * @return int Number of items created.
     */
    public function duplicateToYear(int $schoolId, int $fromYearId, int $toYearId): int
    {
        $source = FeeItem::where('school_id', $schoolId)
            ->where('academic_year_id', $fromYearId)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($source as $item) {
            $exists = FeeItem::where('school_id', $schoolId)
                ->where('academic_year_id', $toYearId)
                ->where('category_id', $item->category_id)
                ->where('class_id', $item->class_id)
                ->where('name', $item->name)
                ->exists();

            if (! $exists) {
                FeeItem::create([
                    'school_id' => $schoolId,
                    'category_id' => $item->category_id,
                    'academic_year_id' => $toYearId,
                    'class_id' => $item->class_id,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'frequency' => $item->frequency,
                    'due_day' => $item->due_day,
                    'is_mandatory' => $item->is_mandatory,
                    'is_active' => true,
                ]);

                $created++;
            }
        }

        if ($created > 0) {
            $this->repository->flush();
        }

        return $created;
    }
}
