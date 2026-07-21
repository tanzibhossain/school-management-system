<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Examination\Models\Exam;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');

        // Basic counts
        $totalStudents = Student::where('school_id', $schoolId)->where('status', 'active')->count();
        $totalStaff = Staff::where('school_id', $schoolId)->where('status', 'active')->count();
        $totalClasses = SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->count();

        // Revenue this month
        $revenueThisMonth = Payment::where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');

        // Outstanding dues
        $outstandingDues = Invoice::where('school_id', $schoolId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '<', Carbon::today())
            ->sum(DB::raw('amount_due - amount_paid - credit_applied'));

        // Attendance rate (today)
        $todayAttendance = StudentAttendance::where('school_id', $schoolId)
            ->where('date', Carbon::today())
            ->count();
        $totalEnrolled = Student::where('school_id', $schoolId)->where('status', 'active')->count();
        $attendanceRate = $totalEnrolled > 0 ? round(($todayAttendance / $totalEnrolled) * 100, 1) : 0;

        // Pending admissions
        $pendingAdmissions = AdmissionApplication::where('school_id', $schoolId)
            ->where('status', 'submitted')
            ->count();

        // Recent students
        $recentStudents = Student::where('school_id', $schoolId)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'name', 'admission_number', 'created_at']);

        // Recent payments
        $recentPayments = Payment::where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->latest('paid_at')
            ->take(5)
            ->get(['id', 'amount', 'paid_at', 'student_id']);

        // Revenue chart (last 6 months)
        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $amount = Payment::where('school_id', $schoolId)
                ->where('is_reversed', false)
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount');
            $revenueChart[] = [
                'month' => $month->format('M Y'),
                'amount' => $amount,
            ];
        }

        // Class strength
        $classStrength = SchoolClass::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->withCount(['studentAcademics' => fn ($q) => $q->where('is_current', true)])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['class' => $c->name, 'count' => $c->student_academics_count]);

        // Attendance trend (last 7 days)
        $attendanceTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $present = StudentAttendance::where('school_id', $schoolId)
                ->where('date', $date)
                ->where('status', 'present')
                ->count();
            $attendanceTrend[] = [
                'date' => $date->format('D, M j'),
                'present' => $present,
            ];
        }

        // Upcoming exams
        $upcomingExams = Exam::where('school_id', $schoolId)
            ->where('status', 'published')
            ->where('start_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->take(5)
            ->get(['id', 'title as name', 'start_date', 'end_date']);

        // Fee defaulters
        $feeDefaulters = Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', Carbon::today())
            ->with('student:id,name,admission_number')
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->student->id ?? null,
                'name' => $inv->student->name ?? 'Unknown',
                'admission_number' => $inv->student->admission_number ?? 'N/A',
                'overdue_count' => 1,
            ])
            ->groupBy('id')
            ->map(fn ($group) => [
                'id' => $group[0]['id'],
                'name' => $group[0]['name'],
                'admission_number' => $group[0]['admission_number'],
                'overdue_count' => count($group),
            ])
            ->sortByDesc('overdue_count')
            ->values()
            ->take(5);

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalStaff',
            'totalClasses',
            'revenueThisMonth',
            'outstandingDues',
            'attendanceRate',
            'totalEnrolled',
            'pendingAdmissions',
            'recentStudents',
            'recentPayments',
            'revenueChart',
            'classStrength',
            'attendanceTrend',
            'upcomingExams',
            'feeDefaulters'
        ));
    }
}
