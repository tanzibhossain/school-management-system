<?php

namespace App\Modules\Announcement\Services;

use App\Models\User;
use App\Modules\Announcement\Events\AnnouncementPublished;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Models\AnnouncementRead;
use App\Modules\Announcement\Repositories\AnnouncementRepository;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AnnouncementService extends BaseService
{
    /** Audience groups visible to each role. */
    private const ROLE_AUDIENCES = [
        'admin'     => ['all', 'teachers', 'students', 'parents'],
        'teacher'   => ['all', 'teachers'],
        'staff'     => ['all', 'teachers'],
        'student'   => ['all', 'students'],
        'accountant'=> ['all', 'teachers'],
    ];

    public function __construct(AnnouncementRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new announcement (draft by default).
     *
     * @param  array<string, mixed>  $data
     * @param  array<array{target_type: string, target_id: int}>  $targets
     */
    public function make(int $schoolId, User $author, array $data, array $targets = []): Announcement
    {
        return DB::transaction(function () use ($schoolId, $author, $data, $targets): Announcement {
            $announcement = Announcement::create(array_merge($data, [
                'school_id'  => $schoolId,
                'created_by' => $author->id,
            ]));

            foreach ($targets as $target) {
                $announcement->targets()->create($target);
            }

            if ($announcement->publish_at !== null && $announcement->publish_at->isPast()) {
                event(new AnnouncementPublished($announcement));
            }

            return $announcement->load(['targets', 'attachments']);
        });
    }

    /**
     * Immediately publish a draft announcement.
     */
    public function publish(Announcement $announcement): Announcement
    {
        $announcement->update(['publish_at' => now()]);
        $this->repository->flush();

        event(new AnnouncementPublished($announcement->fresh()));

        return $announcement->fresh();
    }

    /**
     * Schedule an announcement for future publishing.
     */
    public function schedule(Announcement $announcement, string $publishAt): Announcement
    {
        $announcement->update(['publish_at' => $publishAt]);
        $this->repository->flush();

        return $announcement->fresh();
    }

    /**
     * Expire an announcement immediately.
     */
    public function expire(Announcement $announcement): Announcement
    {
        $announcement->update(['expire_at' => now()]);
        $this->repository->flush();

        return $announcement->fresh();
    }

    /**
     * Mark an announcement as read by a user (idempotent).
     */
    public function markRead(Announcement $announcement, int $userId): AnnouncementRead
    {
        return AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $userId],
            ['read_at' => now()],
        );
    }

    /**
     * Upload a file attachment to MinIO.
     */
    public function attach(Announcement $announcement, UploadedFile $file): Announcement
    {
        $path = $file->store("announcements/{$announcement->school_id}/{$announcement->id}", 'minio');

        $announcement->attachments()->create([
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
        ]);

        $this->repository->flush();

        return $announcement->fresh(['attachments']);
    }

    /**
     * Soft-delete an announcement.
     */
    public function trash(Announcement $announcement): void
    {
        $announcement->update(['is_trash' => true]);
        $this->repository->flush();
    }

    /**
     * Resolve which audience buckets a role can see.
     *
     * @return string[]
     */
    public function audiencesForRole(string $role): array
    {
        return self::ROLE_AUDIENCES[$role] ?? ['all'];
    }
}
