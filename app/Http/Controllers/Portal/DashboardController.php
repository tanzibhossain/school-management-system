<?php

namespace App\Http\Controllers\Portal;

use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Family portal (student + guardian). A student sees their own record; a guardian
 * sees each linked child and can switch between them. All pages are scoped to the
 * currently-selected student.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }
        $student = $ctx['student'];
        $sid = app('current_school_id');

        return view('portal.dashboard', $ctx + [
            'attendance'   => $this->attendanceSummary($student),
            'dues'         => $this->outstanding($student),
            'resultsCount' => ExamResult::where('school_id', $sid)->where('student_id', $student->id)->count(),
            'notices'      => $this->publishedNotices($sid)->take(5)->get(),
        ]);
    }

    public function attendance(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        return view('portal.attendance', $ctx + [
            'summary' => $this->attendanceSummary($ctx['student']),
            'records' => StudentAttendance::where('school_id', app('current_school_id'))
                ->where('student_id', $ctx['student']->id)->orderByDesc('date')->paginate(20),
        ]);
    }

    public function results(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        return view('portal.results', $ctx + [
            'results' => ExamResult::where('school_id', app('current_school_id'))
                ->where('student_id', $ctx['student']->id)->with('exam:id,title')->orderByDesc('calculated_at')->get(),
        ]);
    }

    public function fees(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        return view('portal.fees', $ctx + [
            'invoices'    => Invoice::where('school_id', app('current_school_id'))
                ->where('student_id', $ctx['student']->id)->orderByDesc('due_date')->paginate(20),
            'outstanding' => $this->outstanding($ctx['student']),
        ]);
    }

    public function routine(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        $rows = collect();
        if ($ctx['enrollment']) {
            $rows = ClassRoutine::where('school_id', app('current_school_id'))
                ->where('section_id', $ctx['enrollment']->section_id)
                ->with(['subject:id,name', 'teacher:id,name'])
                ->orderBy('period_id')->get()->groupBy('day_of_week');
        }

        return view('portal.routine', $ctx + ['routine' => $rows]);
    }

    public function notices(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        return view('portal.notices', $ctx + [
            'notices' => $this->publishedNotices(app('current_school_id'))->paginate(15),
        ]);
    }

    public function profile(): View
    {
        $ctx = $this->context();
        if (! $ctx['student']) {
            return view('portal.no-student', $ctx);
        }

        return view('portal.profile', $ctx + [
            'guardians' => StudentGuardian::where('school_id', app('current_school_id'))
                ->where('student_id', $ctx['student']->id)->get(),
        ]);
    }

    // ── Family context ──────────────────────────────────────────────────────

    /**
     * @return array{students: \Illuminate\Support\Collection, student: ?Student, enrollment: ?StudentAcademic, isGuardian: bool}
     */
    private function context(): array
    {
        $sid = app('current_school_id');
        $user = auth()->user();
        $isGuardian = $user->hasRole('parent');

        if ($isGuardian) {
            $childIds = StudentGuardian::where('school_id', $sid)->where('user_id', $user->id)->pluck('student_id');
            $students = Student::where('school_id', $sid)->whereIn('id', $childIds)->where('is_trash', false)->get();
        } else {
            $students = Student::where('school_id', $sid)->where('user_id', $user->id)->where('is_trash', false)->get();
        }

        $selectedId = (int) request('student', session('portal_student_id'));
        $student = $students->firstWhere('id', $selectedId) ?? $students->first();
        if ($student) {
            session(['portal_student_id' => $student->id]);
        }

        $enrollment = $student
            ? StudentAcademic::where('school_id', $sid)->where('student_id', $student->id)
                ->where('is_current', true)->with(['schoolClass:id,name', 'section:id,name', 'shift:id,name'])->first()
            : null;

        return compact('students', 'student', 'enrollment', 'isGuardian');
    }

    private function publishedNotices(int $sid)
    {
        return Announcement::where('school_id', $sid)
            ->whereNotNull('publish_at')->where('publish_at', '<=', now())
            ->orderByDesc('is_pinned')->orderByDesc('publish_at');
    }

    /** @return array{total:int,present:int,percent:?int} */
    private function attendanceSummary(Student $student): array
    {
        $base = StudentAttendance::where('school_id', app('current_school_id'))->where('student_id', $student->id);
        $total = (clone $base)->count();
        $present = (clone $base)->whereIn('status', ['present', 'late', 'half_day'])->count();

        return ['total' => $total, 'present' => $present, 'percent' => $total ? (int) round($present / $total * 100) : null];
    }

    private function outstanding(Student $student): float
    {
        return (float) Invoice::where('school_id', app('current_school_id'))
            ->where('student_id', $student->id)
            ->whereNotIn('status', ['paid', 'cancelled', 'waived'])
            ->sum(DB::raw('amount_due - amount_paid - credit_applied'));
    }
}
