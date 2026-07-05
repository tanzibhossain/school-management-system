<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Repositories\LibraryMemberRepository;
use App\Services\BaseService;

class LibraryMemberService extends BaseService
{
    public function __construct(LibraryMemberRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function make(int $schoolId, array $data): LibraryMember
    {
        $data['school_id'] = $schoolId;
        $data['joined_at'] = $data['joined_at'] ?? now();

        $member = LibraryMember::create($data);
        $this->repository->flush();

        return $member;
    }

    /** @param array<string, mixed> $data */
    public function modify(LibraryMember $member, array $data): LibraryMember
    {
        $member->update($data);
        $this->repository->flush();

        return $member->fresh();
    }

    public function deactivate(LibraryMember $member): void
    {
        $member->update(['is_active' => false]);
        $this->repository->flush();
    }
}
