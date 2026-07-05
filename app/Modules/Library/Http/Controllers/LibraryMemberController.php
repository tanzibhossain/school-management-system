<?php

namespace App\Modules\Library\Http\Controllers;

use App\Modules\Library\Http\Requests\StoreLibraryMemberRequest;
use App\Modules\Library\Http\Requests\UpdateLibraryMemberRequest;
use App\Modules\Library\Http\Resources\LibraryMemberResource;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Repositories\LibraryMemberRepository;
use App\Modules\Library\Services\LibraryMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class LibraryMemberController extends Controller
{
    public function __construct(
        private readonly LibraryMemberService $service,
        private readonly LibraryMemberRepository $repository,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $members = $this->repository->all(app('current_school_id'));

        return LibraryMemberResource::collection($members);
    }

    public function store(StoreLibraryMemberRequest $request): JsonResponse
    {
        $member = $this->service->make(app('current_school_id'), $request->validated());

        return (new LibraryMemberResource($member))->response()->setStatusCode(201);
    }

    public function show(int $id): LibraryMemberResource
    {
        $member = LibraryMember::forSchool(app('current_school_id'))->findOrFail($id);

        return new LibraryMemberResource($member);
    }

    public function update(UpdateLibraryMemberRequest $request, int $id): LibraryMemberResource
    {
        $member = LibraryMember::forSchool(app('current_school_id'))->findOrFail($id);

        return new LibraryMemberResource($this->service->modify($member, $request->validated()));
    }

    public function deactivate(int $id): JsonResponse
    {
        $member = LibraryMember::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->deactivate($member);

        return response()->json(['message' => 'Member deactivated.']);
    }
}
