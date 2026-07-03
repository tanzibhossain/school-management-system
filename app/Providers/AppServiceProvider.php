<?php

namespace App\Providers;

use App\Modules\Sms\Gateways\LogGateway;
use App\Modules\Sms\Gateways\SmsGatewayContract;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Sms\Observers\SmsBatchObserver;
use App\Modules\Sms\Observers\SmsLogObserver;
use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Observers\AcademicYearObserver;
use App\Modules\Academic\Observers\ClassRoutineObserver;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\School\Observers\SchoolObserver;
use App\Modules\School\Observers\SchoolOpeningHourObserver;
use App\Modules\School\Observers\SchoolPhoneObserver;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Observers\AnnouncementObserver;
use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Certificate\Observers\AdmitCardObserver;
use App\Modules\Certificate\Observers\TestimonialObserver;
use App\Modules\Certificate\Observers\TestimonialTemplateObserver;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Observers\StaffAttendanceObserver;
use App\Modules\Attendance\Observers\StudentAttendanceObserver;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\Leave\Observers\LeaveTypeObserver;
use App\Modules\Leave\Observers\StaffLeaveRequestObserver;
use App\Modules\Leave\Observers\StudentLeaveRequestObserver;
use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Loan\Models\StaffLoan;
use App\Modules\Loan\Observers\LoanScheduleObserver;
use App\Modules\Loan\Observers\StaffLoanObserver;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Observers\MarkObserver;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Observers\FeeItemObserver;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardBatchFile;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\IdCard\Observers\IdCardBatchFileObserver;
use App\Modules\IdCard\Observers\IdCardBatchObserver;
use App\Modules\IdCard\Observers\IdCardTemplateObserver;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Observers\InvoiceObserver;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Observers\StaffObserver;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Observers\ExamObserver;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Observers\StudentObserver;
use App\Modules\User\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Sms module — bind the stub gateway; swap for a real provider implementation
        // of SmsGatewayContract here once one is chosen, nothing else changes.
        $this->app->bind(SmsGatewayContract::class, LogGateway::class);
    }

    public function boot(): void
    {
        // ── School module observers ───────────────────────────────────────────
        School::observe(SchoolObserver::class);
        SchoolPhone::observe(SchoolPhoneObserver::class);
        SchoolOpeningHour::observe(SchoolOpeningHourObserver::class);

        // ── Academic module observers ─────────────────────────────────────────
        AcademicYear::observe(AcademicYearObserver::class);
        ClassRoutine::observe(ClassRoutineObserver::class);

        // ── User module observers ─────────────────────────────────────────────
        User::observe(UserObserver::class);

        // ── Announcement module observers ─────────────────────────────────────
        Announcement::observe(AnnouncementObserver::class);

        // ── FeeItem module observers ──────────────────────────────────────────
        FeeItem::observe(FeeItemObserver::class);

        // ── Payment module observers ──────────────────────────────────────────
        Invoice::observe(InvoiceObserver::class);

        // ── Staff module observers ────────────────────────────────────────────
        Staff::observe(StaffObserver::class);

        // ── Student module observers ──────────────────────────────────────────
        Student::observe(StudentObserver::class);

        // ── Examination module observers ──────────────────────────────────────
        Exam::observe(ExamObserver::class);

        // ── Attendance module observers ───────────────────────────────────────
        StudentAttendance::observe(StudentAttendanceObserver::class);
        StaffAttendance::observe(StaffAttendanceObserver::class);

        // ── Mark module observers ─────────────────────────────────────────────
        Mark::observe(MarkObserver::class);

        // ── Leave module observers ────────────────────────────────────────────
        LeaveType::observe(LeaveTypeObserver::class);
        StudentLeaveRequest::observe(StudentLeaveRequestObserver::class);
        StaffLeaveRequest::observe(StaffLeaveRequestObserver::class);

        // ── Loan module observers ─────────────────────────────────────────────
        StaffLoan::observe(StaffLoanObserver::class);
        LoanSchedule::observe(LoanScheduleObserver::class);

        // ── Certificate module observers ──────────────────────────────────────
        AdmitCard::observe(AdmitCardObserver::class);
        TestimonialTemplate::observe(TestimonialTemplateObserver::class);
        Testimonial::observe(TestimonialObserver::class);

        // ── IdCard module observers ───────────────────────────────────────────
        IdCardTemplate::observe(IdCardTemplateObserver::class);
        IdCardBatch::observe(IdCardBatchObserver::class);
        IdCardBatchFile::observe(IdCardBatchFileObserver::class);

        // ── Sms module observers ──────────────────────────────────────────────
        SmsBatch::observe(SmsBatchObserver::class);
        SmsLog::observe(SmsLogObserver::class);
    }
}
