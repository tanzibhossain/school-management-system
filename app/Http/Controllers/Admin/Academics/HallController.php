<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\Examination\Services\HallLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class HallController extends Controller
{
    public function __construct(private readonly HallLayoutService $layout) {}

    public function index(): View
    {
        $halls = ExamHall::where('school_id', app('current_school_id'))
            ->withCount([
                'seats',
                'seats as available_count' => fn ($q) => $q->where('is_available', true),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.academics.halls.index', compact('halls'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $hall = ExamHall::create([
            'school_id' => app('current_school_id'),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'layout_config' => $this->buildLayout($data),
        ]);

        $this->layout->generateSeats($hall);

        return redirect()->route('admin.exam-halls.show', $hall->id)->with('status', __('Hall created with seats.'));
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $hall = ExamHall::where('school_id', $schoolId)->with('seats')->findOrFail($id);
        $rows = $hall->seats->groupBy('row');
        $assigned = ExamSeating::whereIn('hall_seat_id', $hall->seats->pluck('id'))->exists();

        return view('admin.academics.halls.show', compact('hall', 'rows', 'assigned'));
    }

    public function regenerate(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $hall = ExamHall::where('school_id', $schoolId)->findOrFail($id);
        $data = $this->validated($request);

        $hall->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'layout_config' => $this->buildLayout($data),
        ]);

        try {
            $this->layout->generateSeats($hall);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Hall layout regenerated.'));
    }

    public function toggleSeat(int $id, int $seatId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $hall = ExamHall::where('school_id', $schoolId)->findOrFail($id);
        $seat = ExamHallSeat::where('hall_id', $hall->id)->findOrFail($seatId);
        $this->layout->toggleSeat($seat);

        return back()->with('status', "Seat {$seat->label} toggled.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $hall = ExamHall::where('school_id', $schoolId)->with('seats:id,hall_id')->findOrFail($id);

        if (ExamSeating::whereIn('hall_seat_id', $hall->seats->pluck('id'))->exists()) {
            return back()->with('error', __('Cannot delete a hall with active seating assignments.'));
        }

        $hall->delete(); // seats cascade

        return back()->with('status', __('Hall deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'integer', 'min:1', 'max:100'],
            'left_per_row' => ['required', 'integer', 'min:1', 'max:20'],
            'right_per_row' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildLayout(array $data): array
    {
        $sides = [];
        if ((int) $data['left_per_row'] > 0) {
            $sides[] = ['label' => 'L', 'seats_per_row' => (int) $data['left_per_row']];
        }
        if ((int) ($data['right_per_row'] ?? 0) > 0) {
            $sides[] = ['label' => 'R', 'seats_per_row' => (int) $data['right_per_row']];
        }

        return ['rows' => (int) $data['rows'], 'sides' => $sides];
    }
}
