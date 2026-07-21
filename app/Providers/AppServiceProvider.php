<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Observers\AcademicYearObserver;
use App\Modules\Academic\Observers\ClassRoutineObserver;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Observers\AnnouncementObserver;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Observers\StaffAttendanceObserver;
use App\Modules\Attendance\Observers\StudentAttendanceObserver;
use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Certificate\Observers\AdmitCardObserver;
use App\Modules\Certificate\Observers\TestimonialObserver;
use App\Modules\Certificate\Observers\TestimonialTemplateObserver;
use App\Modules\DataImport\Models\ImportBatch;
use App\Modules\DataImport\Observers\ImportBatchObserver;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Observers\ExamObserver;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Observers\FeeItemObserver;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardBatchFile;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\IdCard\Observers\IdCardBatchFileObserver;
use App\Modules\IdCard\Observers\IdCardBatchObserver;
use App\Modules\IdCard\Observers\IdCardTemplateObserver;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\Leave\Observers\LeaveTypeObserver;
use App\Modules\Leave\Observers\StaffLeaveRequestObserver;
use App\Modules\Leave\Observers\StudentLeaveRequestObserver;
use App\Modules\LMS\Gateways\AiCheckerContract;
use App\Modules\LMS\Gateways\AnthropicAiChecker;
use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\LMS\Models\Submission;
use App\Modules\LMS\Models\SubmissionAiCheck;
use App\Modules\LMS\Observers\AssignmentObserver;
use App\Modules\LMS\Observers\CourseObserver;
use App\Modules\LMS\Observers\LessonObserver;
use App\Modules\LMS\Observers\SubmissionAiCheckObserver;
use App\Modules\LMS\Observers\SubmissionObserver;
use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Loan\Models\StaffLoan;
use App\Modules\Loan\Observers\LoanScheduleObserver;
use App\Modules\Loan\Observers\StaffLoanObserver;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Observers\MarkObserver;
use App\Modules\Messaging\Services\MessageService;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\OnlineAdmission\Observers\AdmissionApplicationObserver;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Observers\InvoiceObserver;
use App\Modules\Payroll\Models\PayrollEntry;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Models\SalaryCertificateRequest;
use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Models\StaffSalaryValue;
use App\Modules\Payroll\Observers\PayrollEntryObserver;
use App\Modules\Payroll\Observers\PayrollRunObserver;
use App\Modules\Payroll\Observers\SalaryCertificateRequestObserver;
use App\Modules\Payroll\Observers\SalaryComponentObserver;
use App\Modules\Payroll\Observers\StaffSalaryValueObserver;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\School\Observers\ModuleSettingObserver;
use App\Modules\School\Observers\SchoolObserver;
use App\Modules\School\Observers\SchoolOpeningHourObserver;
use App\Modules\School\Observers\SchoolPhoneObserver;
use App\Modules\Sms\Gateways\LogGateway;
use App\Modules\Sms\Gateways\SmsGatewayContract;
use App\Modules\Sms\Models\SmsBatch;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Sms\Observers\SmsBatchObserver;
use App\Modules\Sms\Observers\SmsLogObserver;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Observers\StaffObserver;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Observers\StudentObserver;
use App\Modules\User\Observers\UserObserver;
use App\Modules\Website\Models\Menu;
use App\Modules\Website\Models\MenuItem;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\PageRedirect;
use App\Modules\Website\Models\PageTemplate;
use App\Modules\Website\Models\SiteLayout;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Models\WebsiteMedia;
use App\Modules\Website\Observers\MenuItemObserver;
use App\Modules\Website\Observers\MenuObserver;
use App\Modules\Website\Observers\PageLayoutObserver;
use App\Modules\Website\Observers\PageObserver;
use App\Modules\Website\Observers\PageRedirectObserver;
use App\Modules\Website\Observers\PageTemplateObserver;
use App\Modules\Website\Observers\SiteLayoutObserver;
use App\Modules\Website\Observers\SiteSettingObserver;
use App\Modules\Website\Observers\WebsiteMediaObserver;
use App\Modules\Website\Services\PublicPortalService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Sms module — bind the stub gateway; swap for a real provider implementation
        // of SmsGatewayContract here once one is chosen, nothing else changes.
        $this->app->bind(SmsGatewayContract::class, LogGateway::class);

        // LMS module — real Anthropic API integration (per the confirmed decision,
        // unlike Sms's stub gateway). Swap this binding for a different provider
        // by implementing AiCheckerContract; nothing else in the module changes.
        $this->app->bind(AiCheckerContract::class, AnthropicAiChecker::class);
    }

    public function boot(): void
    {
        // ── School module observers ───────────────────────────────────────────
        School::observe(SchoolObserver::class);
        SchoolPhone::observe(SchoolPhoneObserver::class);
        SchoolOpeningHour::observe(SchoolOpeningHourObserver::class);
        ModuleSetting::observe(ModuleSettingObserver::class);

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

        // ── DataImport module observers ───────────────────────────────────────
        ImportBatch::observe(ImportBatchObserver::class);

        // ── OnlineAdmission module observers ──────────────────────────────────
        AdmissionApplication::observe(AdmissionApplicationObserver::class);

        // ── Website module observers ──────────────────────────────────────────
        Page::observe(PageObserver::class);
        PageLayout::observe(PageLayoutObserver::class);
        PageRedirect::observe(PageRedirectObserver::class);
        SiteLayout::observe(SiteLayoutObserver::class);
        SiteSetting::observe(SiteSettingObserver::class);
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        PageTemplate::observe(PageTemplateObserver::class);
        WebsiteMedia::observe(WebsiteMediaObserver::class);

        // ── Payroll module observers ──────────────────────────────────────────
        SalaryComponent::observe(SalaryComponentObserver::class);
        StaffSalaryValue::observe(StaffSalaryValueObserver::class);
        PayrollRun::observe(PayrollRunObserver::class);
        PayrollEntry::observe(PayrollEntryObserver::class);
        SalaryCertificateRequest::observe(SalaryCertificateRequestObserver::class);

        // ── LMS module observers ──────────────────────────────────────────────
        Course::observe(CourseObserver::class);
        Lesson::observe(LessonObserver::class);
        Assignment::observe(AssignmentObserver::class);
        Submission::observe(SubmissionObserver::class);
        SubmissionAiCheck::observe(SubmissionAiCheckObserver::class);

        // Public site header — share the notice ticker (latest visible announcements)
        // with the shared header partial, so every controller needn't pass it.
        View::composer('public.partials.header', function ($view): void {
            $school = School::current();
            $view->with('ticker', $school
                ? app(PublicPortalService::class)->notices($school->id)->take(8)
                : collect());
            $view->with('navMenu', $school
                ? Menu::forSchool($school->id)
                    ->with(['items.children.page', 'items.page'])->first()
                : null);
        });

        // Staff/family portal shells — share the unread message count for the
        // sidebar "Messages" badge, so every portal controller needn't pass it.
        View::composer(['layouts.staff', 'layouts.portal'], function ($view): void {
            $user = auth()->user();
            $view->with('messagesUnread', $user
                ? app(MessageService::class)->unreadCountFor($user->school_id, $user->id)
                : 0);
        });
    }
}
