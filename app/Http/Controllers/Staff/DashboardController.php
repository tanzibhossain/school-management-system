<?php

namespace App\Http\Controllers\Staff;

use App\Modules\Academic\Models\Section;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Staff / teacher portal. Surfaces what a teacher needs day-to-day: the classes
 * they lead, their subject, student counts, and recent notices.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        [$sid, $staff] = $this->context();

        $sections = $staff
            ? Section::where('school_id', $sid)->where('class_teacher_id', $staff->id)
                ->where('is_trash', false)->with(['schoolClass:id,name', 'shift:id,name'])->get()
            : collect();

        $sectionIds = $sections->pluck('id');
        $studentCount = $sectionIds->isNotEmpty()
            ? StudentAcademic::where('school_id', $sid)->whereIn('section_id', $sectionIds)
                ->where('is_current', true)->distinct('student_id')->count('student_id')
            : 0;

        $notices = Announcement::where('school_id', $sid)
            ->whereNotNull('publish_at')->where('publish_at', '<=', now())
            ->orderByDesc('is_pinned')->orderByDesc('publish_at')->take(5)->get();

        return view('staff.dashboard', [
            'staff'        => $staff,
            'sections'     => $sections,
            'studentCount' => $studentCount,
            'notices'      => $notices,
        ]);
    }

    public function profile(): View
    {
        [, $staff] = $this->context();

        return view('staff.profile', ['staff' => $staff?->load(['designation', 'department', 'subject'])]);
    }

    public function notices(): View
    {
        $sid = app('current_school_id');
        $notices = Announcement::where('school_id', $sid)
            ->whereNotNull('publish_at')->where('publish_at', '<=', now())
            ->orderByDesc('is_pinned')->orderByDesc('publish_at')->paginate(15);

        return view('staff.notices', ['notices' => $notices]);
    }

    /** @return array{0:int,1:?Staff} */
    private function context(): array
    {
        $sid = app('current_school_id');
        $staff = Staff::where('school_id', $sid)->where('user_id', auth()->id())->first();

        return [$sid, $staff];
    }
}
