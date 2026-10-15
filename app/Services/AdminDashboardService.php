<?php

namespace App\Services;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Examination\Models\Exam;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregates for the admin dashboard (Admin\DashboardController).
 * Spans eight modules, so it lives here (shared) rather than in any one of
 * them. Uncached on purpose: it's financial/attendance data an admin expects
 * to be current the moment they open the page.
 */
class AdminDashboardService
{
    /**
     * Everything admin/dashboard.blade.php expects, keyed by view variable.
     *
     * @return array<string, mixed>
     */
    public function forSchool(int $schoolId): array
    {
        $totalStudents = Student::where('school_id', $schoolId)->where('status', 'active')->count();

        // Attendance rate (today) — records today over active students.
        $todayAttendance = StudentAttendance::where('school_id', $schoolId)
            ->where('date', Carbon::today())
            ->count();
        $attendanceRate = $totalStudents > 0 ? round(($todayAttendance / $totalStudents) * 100, 1) : 0;

        return [
            'totalStudents' => $totalStudents,
            'totalStaff' => Staff::where('school_id', $schoolId)->where('status', 'active')->count(),
            'totalClasses' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->count(),
            'revenueThisMonth' => $this->revenueBetween($schoolId, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
            'outstandingDues' => $this->overdueInvoices($schoolId)
                ->sum(DB::raw('amount_due - amount_paid - credit_applied')),
            'attendanceRate' => $attendanceRate,
            'totalEnrolled' => $totalStudents,
            'pendingAdmissions' => AdmissionApplication::where('school_id', $schoolId)
                ->where('status', 'submitted')
                ->count(),
            'recentStudents' => Student::where('school_id', $schoolId)
                ->latest('created_at')
                ->take(5)
                ->get(['id', 'name', 'admission_number', 'created_at']),
            'recentPayments' => Payment::where('school_id', $schoolId)
                ->where('is_reversed', false)
                ->latest('paid_at')
                ->take(5)
                ->get(['id', 'amount', 'paid_at', 'student_id']),
            'revenueChart' => $this->revenueChart($schoolId),
            'classStrength' => $this->classStrength($schoolId),
            'attendanceTrend' => $this->attendanceTrend($schoolId),
            'upcomingExams' => Exam::where('school_id', $schoolId)
                ->where('status', 'published')
                ->where('start_date', '>=', Carbon::today())
                ->orderBy('start_date')
                ->take(5)
                ->get(['id', 'title as name', 'start_date', 'end_date']),
            'feeDefaulters' => $this->feeDefaulters($schoolId),
        ];
    }

    private function revenueBetween(int $schoolId, Carbon $start, Carbon $end): mixed
    {
        return Payment::where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
    }

    /**
     * Unpaid/partial (not paid, not cancelled) invoices past their due date.
     *
     * @return Builder<Invoice>
     */
    private function overdueInvoices(int $schoolId): Builder
    {
        return Invoice::where('school_id', $schoolId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '<', Carbon::today());
    }

    /**
     * Last 6 months, oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function revenueChart(int $schoolId): array
    {
        $chart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chart[] = [
                'month' => $month->format('M Y'),
                'amount' => $this->revenueBetween($schoolId, $month->copy()->startOfMonth(), $month->copy()->endOfMonth()),
            ];
        }

        return $chart;
    }

    private function classStrength(int $schoolId): Collection
    {
        return SchoolClass::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->withCount(['studentAcademics' => fn ($q) => $q->where('is_current', true)])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['class' => $c->name, 'count' => $c->student_academics_count]);
    }

    /**
     * Present count per day for the last 7 days, oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attendanceTrend(int $schoolId): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $trend[] = [
                'date' => $date->format('D, M j'),
                'present' => StudentAttendance::where('school_id', $schoolId)
                    ->where('date', $date)
                    ->where('status', 'present')
                    ->count(),
            ];
        }

        return $trend;
    }

    /**
     * Up to 5 students with overdue invoices, most-overdue first. Counts are
     * taken over the 5 oldest overdue invoices only (unchanged behaviour).
     */
    private function feeDefaulters(int $schoolId): Collection
    {
        return Invoice::where('school_id', $schoolId)
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
    }
}
