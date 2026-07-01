<?php

namespace App\Modules\School\Services;

use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\School\Repositories\SchoolRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class SchoolService extends BaseService
{
    public function __construct(SchoolRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Return the school profile with phones and opening hours.
     */
    public function getSettings(): ?School
    {
        return $this->repository->getCurrent();
    }

    /**
     * Update the main school profile fields.
     */
    public function updateSettings(School $school, array $data): School
    {
        return $this->repository->update($school, $data);
    }

    /**
     * Replace the full phone list in a single transaction.
     * If no phone has is_primary = true, the first entry is promoted automatically.
     */
    public function syncPhones(int $schoolId, array $phones): void
    {
        DB::transaction(function () use ($schoolId, $phones) {
            SchoolPhone::where('school_id', $schoolId)->delete();

            $hasPrimary = collect($phones)->contains('is_primary', true);

            foreach ($phones as $index => $phone) {
                SchoolPhone::create([
                    'school_id'  => $schoolId,
                    'phone'      => $phone['phone'],
                    'label'      => $phone['label'] ?? null,
                    'is_primary' => $phone['is_primary'] ?? (! $hasPrimary && $index === 0),
                ]);
            }
        });

        $this->repository->flush();
    }

    /**
     * Update a single day's opening hours.
     */
    public function updateOpeningHour(int $schoolId, int $dayOfWeek, array $data): SchoolOpeningHour
    {
        $hour = SchoolOpeningHour::where('school_id', $schoolId)
            ->where('day_of_week', $dayOfWeek)
            ->firstOrFail();

        $hour->update($data);
        $this->repository->flush();

        return $hour->fresh();
    }
}
