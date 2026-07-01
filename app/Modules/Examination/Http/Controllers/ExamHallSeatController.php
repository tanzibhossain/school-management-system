<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Resources\ExamHallSeatResource;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Services\HallLayoutService;
use Illuminate\Routing\Controller;

class ExamHallSeatController extends Controller
{
    public function __construct(private readonly HallLayoutService $layoutService) {}

    /**
     * Toggle a seat's is_available flag.
     * Admins use this to block individual seats (broken bench, pillar, projector leg, etc.)
     * without needing to regenerate the whole hall.
     */
    public function toggle(int $hallId, int $seatId): ExamHallSeatResource
    {
        // Ensure the hall belongs to this school
        ExamHall::where('school_id', app('current_school_id'))->findOrFail($hallId);

        $seat = ExamHallSeat::where('hall_id', $hallId)->findOrFail($seatId);

        return new ExamHallSeatResource($this->layoutService->toggleSeat($seat));
    }
}
