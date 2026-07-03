<?php

namespace App\Modules\IdCard\Services;

use App\Models\User;
use App\Modules\IdCard\Jobs\GenerateIdCardBatchJob;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves which students/staff a batch covers, builds each one's flat
 * "card data" array for IdCardRenderer, and kicks off the queued job.
 * Shared between the controller (to count + create the batch) and
 * GenerateIdCardBatchJob (to actually fetch the records to render).
 */
class IdCardBatchService
{
    /**
     * @param  array{class_id?: int|null, section_id?: int|null, target_ids?: array<int>|null}  $filters
     */
    public function request(int $schoolId, string $type, int $templateId, string $scope, array $filters, User $user): IdCardBatch
    {
        $template = IdCardTemplate::forSchool($schoolId)->ofType($type)->findOrFail($templateId);

        $totalCount = $this->targetQuery($schoolId, $type, $scope, $filters)->count();

        $batch = IdCardBatch::create([
            'school_id' => $schoolId,
            'type' => $type,
            'template_id' => $template->id,
            'scope' => $scope,
            'class_id' => $filters['class_id'] ?? null,
            'section_id' => $filters['section_id'] ?? null,
            'target_ids' => $filters['target_ids'] ?? null,
            'total_count' => $totalCount,
            'status' => 'queued',
            'requested_by' => $user->id,
        ]);

        GenerateIdCardBatchJob::dispatch($batch->id);

        // Under QUEUE_CONNECTION=sync (tests, and any deployment without Horizon
        // configured), dispatch() above already ran the job to completion — refetch
        // so the returned instance reflects the final status, not the stale 'queued'
        // one held in memory from before dispatch.
        return $batch->fresh(['template', 'files']);
    }

    /**
     * @param  array{class_id?: int|null, section_id?: int|null, target_ids?: array<int>|null}  $filters
     * @return Builder<Student>|Builder<Staff>
     */
    public function targetQuery(int $schoolId, string $type, string $scope, array $filters): Builder
    {
        $query = $type === 'student' ? Student::query() : Staff::query();
        $query->where('school_id', $schoolId)->where('is_trash', false)->where('status', 'active');

        if ($scope === 'single') {
            $query->whereIn('id', $filters['target_ids'] ?? []);
        } elseif ($scope === 'class' && $type === 'student') {
            $classId = $filters['class_id'] ?? null;
            $sectionId = $filters['section_id'] ?? null;
            $query->whereHas('currentAcademic', function (Builder $q) use ($classId, $sectionId): void {
                $q->where('is_current', true)->where('class_id', $classId);
                if ($sectionId) {
                    $q->where('section_id', $sectionId);
                }
            });
        }
        // scope=all (or scope=class for staff, which has no class concept) => no extra filter.

        return $query;
    }

    /** Resolve once per batch (not per record) — same for every card. */
    public function schoolPhone(School $school): ?string
    {
        return $school->phones()->where('is_primary', true)->first()?->phone
            ?? $school->phones()->first()?->phone;
    }

    /**
     * Flat field map consumed by IdCardRenderer. $logoDataUri and $phone are
     * resolved once per batch (not per record) by the job, since both are the
     * same for every card.
     *
     * @return array<string, mixed>
     */
    public function cardData(string $type, Model $record, School $school, ?string $logoDataUri, ?string $phone): array
    {
        $labels = [];
        $values = [];

        if ($type === 'student') {
            /** @var Student $record */
            $academic = $record->currentAcademic;
            $className = $academic?->schoolClass?->name;
            $sectionName = $academic?->section?->name;

            $labels = [
                'id' => 'Student ID',
                'class_section' => 'Class',
                'academic_year' => 'Academic Year',
                'blood_group' => 'Blood Group',
                'school_phone' => 'Phone',
            ];
            $values = [
                'id' => $record->student_id ?: $record->admission_number,
                'class_section' => trim(($className ?? '').($sectionName ? " - {$sectionName}" : '')),
                'academic_year' => $academic?->year?->year,
                'blood_group' => $record->blood_group,
                'school_phone' => $phone,
            ];
        } else {
            /** @var Staff $record */
            $labels = [
                'id' => 'Employee ID',
                'designation' => 'Designation',
                'department' => 'Department',
                'blood_group' => 'Blood Group',
                'school_phone' => 'Phone',
            ];
            $values = [
                'id' => $record->employee_id,
                'designation' => $record->designation?->name,
                'department' => $record->department?->name,
                'blood_group' => $record->blood_group,
                'school_phone' => $phone,
            ];
        }

        return [
            'name' => $record->name,
            'photo_url' => $this->resolveDataUri($record->photo),
            'logo_url' => $logoDataUri,
            'school_name_header' => $school->name,
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * dompdf doesn't fetch remote URLs by default, so photos/logos are
     * inlined as base64 data URIs read straight from MinIO rather than
     * linked by signed URL.
     */
    public function resolveDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('minio')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('minio')->get($path);
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return "data:{$mime};base64,".base64_encode($bytes);
    }
}
