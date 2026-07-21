<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class RoutineSetupController extends Controller
{
    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.setup.routine-setup.index', [
            'periods' => RoutinePeriod::where('school_id', $schoolId)->where('is_trash', false)->orderBy('start_time')->get(),
            'rooms' => RoutineRoom::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);
        RoutinePeriod::create($data + ['school_id' => app('current_school_id')]);

        return back()->with('status', __('Period Added.'));
    }

    public function destroyPeriod(int $id): RedirectResponse
    {
        RoutinePeriod::where('school_id', app('current_school_id'))->findOrFail($id)->update(['is_trash' => true]);

        return back()->with('status', __('Period Removed.'));
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        RoutineRoom::create($data + ['school_id' => app('current_school_id')]);

        return back()->with('status', __('Room Added.'));
    }

    public function destroyRoom(int $id): RedirectResponse
    {
        RoutineRoom::where('school_id', app('current_school_id'))->findOrFail($id)->update(['is_trash' => true]);

        return back()->with('status', __('Room Removed.'));
    }
}
