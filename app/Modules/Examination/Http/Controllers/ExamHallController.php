<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Requests\StoreExamHallRequest;
use App\Modules\Examination\Http\Requests\UpdateExamHallRequest;
use App\Modules\Examination\Http\Resources\ExamHallCollection;
use App\Modules\Examination\Http\Resources\ExamHallResource;
use App\Modules\Examination\Http\Resources\ExamHallSeatResource;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Repositories\ExamHallRepository;
use App\Modules\Examination\Services\HallLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RuntimeException;

class ExamHallController extends Controller
{
    public function __construct(
        private readonly HallLayoutService $layoutService,
        private readonly ExamHallRepository $repository,
    ) {}

    public function index(): ExamHallCollection
    {
        return new ExamHallCollection($this->repository->all(app('current_school_id')));
    }

    public function show(int $id): ExamHallResource
    {
        $hall = ExamHall::where('school_id', app('current_school_id'))->findOrFail($id);

        return new ExamHallResource($hall);
    }

    public function store(StoreExamHallRequest $request): JsonResponse
    {
        $hall = ExamHall::create([
            'school_id' => app('current_school_id'),
            ...$request->validated(),
        ]);

        return (new ExamHallResource($hall))->response()->setStatusCode(201);
    }

    public function update(UpdateExamHallRequest $request, int $id): ExamHallResource
    {
        $hall = ExamHall::where('school_id', app('current_school_id'))->findOrFail($id);
        $hall->update($request->validated());

        return new ExamHallResource($hall->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $hall = ExamHall::where('school_id', app('current_school_id'))->findOrFail($id);
        $hall->delete();

        return response()->json(['message' => 'Hall deleted.']);
    }

    /**
     * Generate (or regenerate) seats from the hall's layout_config.
     * Returns seat count and a summary.
     */
    public function generateSeats(int $id): JsonResponse
    {
        $hall = ExamHall::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            $count = $this->layoutService->generateSeats($hall);

            return response()->json([
                'message' => "{$count} seats generated for {$hall->name}.",
                'total_seats' => $count,
                'available_seats' => $count,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * List all seats for a hall (useful for the seat-map UI).
     */
    public function seats(int $id): JsonResponse
    {
        $hall = ExamHall::where('school_id', app('current_school_id'))->findOrFail($id);
        $seats = $hall->seats()->get();

        return response()->json([
            'data' => ExamHallSeatResource::collection($seats),
            'total_seats' => $seats->count(),
            'available_seats' => $seats->where('is_available', true)->count(),
        ]);
    }
}
