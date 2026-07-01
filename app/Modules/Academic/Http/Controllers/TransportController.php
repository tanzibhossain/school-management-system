<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreTransportRequest;
use App\Modules\Academic\Http\Requests\UpdateTransportRequest;
use App\Modules\Academic\Http\Resources\TransportResource;
use App\Modules\Academic\Models\Transport;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TransportController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = Transport::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return TransportResource::collection($items);
    }

    public function store(StoreTransportRequest $request): TransportResource
    {
        $item = Transport::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new TransportResource($item);
    }

    public function show(int $id): TransportResource
    {
        $item = Transport::where('school_id', app('current_school_id'))->findOrFail($id);

        return new TransportResource($item);
    }

    public function update(UpdateTransportRequest $request, int $id): TransportResource
    {
        $item = Transport::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new TransportResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = Transport::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
