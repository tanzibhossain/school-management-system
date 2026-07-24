<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\Academics\AttendanceController;
use App\Http\Controllers\Admin\Academics\ExamController;
use App\Http\Controllers\Admin\Academics\ExamMarkController;
use App\Http\Controllers\Admin\Academics\ExamSeatingController;
use App\Http\Controllers\Admin\Academics\ExamTypeController;
use App\Http\Controllers\Admin\Academics\HallController;
use App\Http\Controllers\Admin\Academics\MarkSettingController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Certificates\AdmitCardController;
use App\Http\Controllers\Admin\Certificates\IdCardBatchController;
use App\Http\Controllers\Admin\Certificates\IdCardTemplateController;
use App\Http\Controllers\Admin\Certificates\TemplateController as CertTemplateController;
use App\Http\Controllers\Admin\Certificates\TestimonialController;
use App\Http\Controllers\Admin\Comms\AnnouncementController;
use App\Http\Controllers\Admin\Comms\ContactMessageController;
use App\Http\Controllers\Admin\Comms\MessageController;
use App\Http\Controllers\Admin\Comms\ReportController;
use App\Http\Controllers\Admin\Comms\SmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Finance\FeeCategoryController;
use App\Http\Controllers\Admin\Finance\FeeDiscountController;
use App\Http\Controllers\Admin\Finance\FeeItemController;
use App\Http\Controllers\Admin\Finance\InvoiceController;
use App\Http\Controllers\Admin\Finance\PaymentConfigController;
use App\Http\Controllers\Admin\Finance\PaymentController;
use App\Http\Controllers\Admin\Finance\RefundController;
use App\Http\Controllers\Admin\Finance\StudentCreditController;
use App\Http\Controllers\Admin\Hr\LeaveTypeController;
use App\Http\Controllers\Admin\Hr\StaffLeaveController;
use App\Http\Controllers\Admin\Hr\StaffLoanController;
use App\Http\Controllers\Admin\Hr\StudentLeaveController;
use App\Http\Controllers\Admin\Modules\Library\BookController;
use App\Http\Controllers\Admin\Modules\Library\BorrowController;
use App\Http\Controllers\Admin\Modules\Library\MemberController;
use App\Http\Controllers\Admin\Modules\Lms\AssignmentController as LmsAssignmentController;
use App\Http\Controllers\Admin\Modules\Lms\CourseController as LmsCourseController;
use App\Http\Controllers\Admin\Modules\Lms\LessonController as LmsLessonController;
use App\Http\Controllers\Admin\Modules\Lms\SubmissionController as LmsSubmissionController;
use App\Http\Controllers\Admin\Modules\Payroll\PayrollRunController;
use App\Http\Controllers\Admin\Modules\Payroll\SalaryComponentController;
use App\Http\Controllers\Admin\Modules\Payroll\StaffSalaryController;
use App\Http\Controllers\Admin\Modules\Transport\DriverController;
use App\Http\Controllers\Admin\Modules\Transport\RouteController;
use App\Http\Controllers\Admin\Modules\Transport\VehicleController;
use App\Http\Controllers\Admin\People\AdmissionController;
use App\Http\Controllers\Admin\People\DataImportController;
use App\Http\Controllers\Admin\People\StaffController;
use App\Http\Controllers\Admin\People\StaffReferenceController;
use App\Http\Controllers\Admin\People\StudentController;
use App\Http\Controllers\Admin\People\UserController;
use App\Http\Controllers\Admin\Setup\AcademicYearController;
use App\Http\Controllers\Admin\Setup\ClassController;
use App\Http\Controllers\Admin\Setup\LanguageController;
use App\Http\Controllers\Admin\Setup\ModuleController;
use App\Http\Controllers\Admin\Setup\ReferenceController;
use App\Http\Controllers\Admin\Setup\RoutineController;
use App\Http\Controllers\Admin\Setup\RoutineSetupController;
use App\Http\Controllers\Admin\Setup\SchoolController;
use App\Http\Controllers\Admin\Setup\SectionController;
use App\Http\Controllers\Admin\Setup\SubjectController;
use App\Http\Controllers\Admin\Website\MediaController;
use App\Http\Controllers\Admin\Website\MenuController;
use App\Http\Controllers\Admin\Website\PageController as WebsitePageController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Payment\WebhookController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController as PublicPageController;
use App\Http\Controllers\Staff\ClockController;
use App\Http\Controllers\Staff\LeaveController;
use App\Http\Controllers\Staff\MarkController;
use App\Modules\Language\Models\Language;
use Illuminate\Support\Facades\Route;

// Public school homepage.
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public online-admission submission (form rendered by the admission_form block).
Route::post('/admission', [App\Http\Controllers\Public\AdmissionController::class, 'submit'])
    ->middleware('throttle:10,1')->name('admission.submit');

// Public contact-form submission (form rendered by the contact block).
Route::post('/contact', [ContactController::class, 'submit'])
    ->middleware('throttle:10,1')->name('contact.submit');

// Streams a Website media library file from the private "minio" bucket —
// see App\Http\Controllers\Public\WebsiteMediaController's docblock.
Route::get('/media/website/{id}', [App\Http\Controllers\Public\WebsiteMediaController::class, 'show'])
    ->whereNumber('id')->name('website-media.show');

Route::middleware('guest')->group(function (): void {
    // Family portal (student + guardian) — the default login.
    Route::get('/login', [LoginController::class, 'showFamily'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
    // Admin console.
    Route::get('/admin/login', [LoginController::class, 'showAdmin'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'login'])->middleware('throttle:login');
    // Staff & teachers.
    Route::get('/staff/login', [LoginController::class, 'showStaff'])->name('staff.login');
    Route::post('/staff/login', [LoginController::class, 'login'])->middleware('throttle:login');

    // Second step of login for accounts with two-factor enabled — see LoginController::login().
    // Throttled tightly: a TOTP code is only 6 digits (1,000,000 possibilities).
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:two-factor')->name('two-factor.verify');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Email-change confirmation link (mailed to the NEW address) — one route shared
// by all three portals, since the account itself isn't portal-specific.
Route::get('/account/email/confirm/{user}/{token}', [AccountController::class, 'confirmEmailChange'])
    ->middleware(['auth', 'signed'])->name('account.email.confirm');

// "Wasn't you?" cancel link mailed to the OLD address — deliberately NOT
// behind 'auth' (the real owner may be locked out already); the signature
// plus a live token check in the service is what guards this instead.
Route::get('/account/email/cancel/{user}/{token}', [AccountController::class, 'cancelEmailChangeExternal'])
    ->middleware('signed')->name('account.email.cancel-external');

// Self-service account settings (name, password, email, 2FA, sessions) — the
// same routes/controller are registered under each portal's prefix below so
// `route('admin.account')` / `staff.account` / `portal.account` all resolve;
// AccountController reads the portal off the current route name.
$accountRoutes = function (): void {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/name', [AccountController::class, 'updateName'])->name('account.update-name');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.update-password');
    Route::post('/account/email', [AccountController::class, 'requestEmailChange'])->name('account.request-email');
    Route::delete('/account/email', [AccountController::class, 'cancelEmailChange'])->name('account.cancel-email');
    Route::get('/account/2fa/enable', [AccountController::class, 'showEnableTwoFactor'])->name('account.2fa.enable');
    Route::post('/account/2fa/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.2fa.confirm');
    Route::delete('/account/2fa', [AccountController::class, 'disableTwoFactor'])->name('account.2fa.disable');
    Route::post('/account/2fa/recovery-codes', [AccountController::class, 'regenerateRecoveryCodes'])->name('account.2fa.recovery-codes');
    Route::delete('/account/sessions/{history}', [AccountController::class, 'revokeSession'])->whereNumber('history')->name('account.sessions.revoke');
    Route::post('/account/sessions/revoke-others', [AccountController::class, 'revokeOtherSessions'])->name('account.sessions.revoke-others');
};

// Language switcher — anyone (guest or logged in) can pick an active language.
Route::get('/language/{code}', function (string $code) {
    if (Language::activeCached()->contains(fn ($l) => $l->code === $code)) {
        session(['app_locale' => $code]);
    }

    return back();
})->name('language.switch');

// ── Staff / teacher portal ───────────────────────────────────────────────────
Route::middleware(['auth', 'school', 'role:teacher|accountant|librarian|receptionist'])
    ->prefix('staff')->name('staff.')->group(function () use ($accountRoutes): void {
        Route::get('/', [App\Http\Controllers\Staff\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/attendance', [App\Http\Controllers\Staff\AttendanceController::class, 'index'])->name('attendance');
        Route::post('/attendance', [App\Http\Controllers\Staff\AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/routine', [App\Http\Controllers\Staff\DashboardController::class, 'routine'])->name('routine');
        Route::get('/marks', [MarkController::class, 'index'])->name('marks');
        Route::get('/marks/{examId}/divisions/{divisionId}', [MarkController::class, 'entry'])->whereNumber('examId')->whereNumber('divisionId')->name('marks.entry');
        Route::post('/marks/{examId}/divisions/{divisionId}', [MarkController::class, 'saveEntry'])->whereNumber('examId')->whereNumber('divisionId')->name('marks.save');
        Route::get('/messages', [App\Http\Controllers\Staff\MessageController::class, 'index'])->name('messages');
        Route::post('/messages', [App\Http\Controllers\Staff\MessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/{id}', [App\Http\Controllers\Staff\MessageController::class, 'show'])->whereNumber('id')->name('messages.show');
        Route::post('/messages/{id}/reply', [App\Http\Controllers\Staff\MessageController::class, 'reply'])->whereNumber('id')->name('messages.reply');
        Route::get('/my-attendance', [ClockController::class, 'index'])->name('clock');
        Route::post('/my-attendance/punch', [ClockController::class, 'punch'])->name('clock.punch');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::patch('/leave/{id}/cancel', [LeaveController::class, 'cancel'])->whereNumber('id')->name('leave.cancel');
        Route::get('/notices', [App\Http\Controllers\Staff\DashboardController::class, 'notices'])->name('notices');
        Route::get('/profile', [App\Http\Controllers\Staff\DashboardController::class, 'profile'])->name('profile');
        $accountRoutes();
    });

// ── Family portal (student + guardian) ─────────────────────────────────────────
Route::middleware(['auth', 'school', 'role:student|parent'])
    ->prefix('portal')->name('portal.')->group(function () use ($accountRoutes): void {
        $c = App\Http\Controllers\Portal\DashboardController::class;
        Route::get('/', [$c, 'index'])->name('dashboard');
        Route::get('/attendance', [$c, 'attendance'])->name('attendance');
        Route::get('/results', [$c, 'results'])->name('results');
        Route::get('/results/{examId}/marksheet', [$c, 'marksheet'])->whereNumber('examId')->name('results.marksheet');
        Route::get('/fees', [$c, 'fees'])->name('fees');
        Route::post('/pay/initiate', [App\Http\Controllers\Portal\PaymentController::class, 'initiate'])->name('pay.initiate');
        Route::get('/routine', [$c, 'routine'])->name('routine');
        Route::get('/leave', [$c, 'leave'])->name('leave');
        Route::post('/leave', [$c, 'leaveStore'])->name('leave.store');
        Route::patch('/leave/{id}/cancel', [$c, 'leaveCancel'])->whereNumber('id')->name('leave.cancel');
        Route::get('/notices', [$c, 'notices'])->name('notices');
        Route::get('/profile', [$c, 'profile'])->name('profile');

        $mc = App\Http\Controllers\Portal\MessageController::class;
        Route::get('/messages', [$mc, 'index'])->name('messages');
        Route::post('/messages', [$mc, 'store'])->name('messages.store');
        Route::get('/messages/{id}', [$mc, 'show'])->whereNumber('id')->name('messages.show');
        Route::post('/messages/{id}/reply', [$mc, 'reply'])->whereNumber('id')->name('messages.reply');
        $accountRoutes();
    });

// Gateway browser-return for family portal payments — public (the gateway drives
// the redirect); the school + invoice are resolved from the cached payment id.
Route::get('/portal/pay/bkash/callback', [App\Http\Controllers\Portal\PaymentController::class, 'bkashCallback'])
    ->name('portal.pay.bkash.callback');

// Stripe redirects the browser back here (GET). success_url carries session_id;
// cancel_url omits it. Public — the invoice is resolved from the cached session.
Route::get('/portal/pay/stripe/return', [App\Http\Controllers\Portal\PaymentController::class, 'stripeReturn'])
    ->name('portal.pay.stripe.return');

// PayPal redirects the browser back here (GET). Approval carries ?token={ORDER_ID};
// cancel carries ?cancel=1. Public — the invoice is resolved from the cached order.
Route::get('/portal/pay/paypal/return', [App\Http\Controllers\Portal\PaymentController::class, 'paypalReturn'])
    ->name('portal.pay.paypal.return');

// SSLCommerz POSTs the browser back here (success/fail/cancel). CSRF-exempt (see
// bootstrap/app.php) and public — the invoice is resolved from tran_id.
Route::match(['get', 'post'], '/portal/pay/sslcommerz/{result}', [App\Http\Controllers\Portal\PaymentController::class, 'sslcommerzReturn'])
    ->whereIn('result', ['success', 'fail', 'cancel'])
    ->name('portal.pay.sslcommerz.return');

// Server-to-server gateway webhooks — authoritative confirmation, public + CSRF-exempt
// (bootstrap/app.php); the signature is the trust boundary.
Route::post('/payments/webhook/stripe', [WebhookController::class, 'stripe'])
    ->name('payments.webhook.stripe');
Route::post('/payments/webhook/paypal', [WebhookController::class, 'paypal'])
    ->name('payments.webhook.paypal');

Route::middleware(['auth', 'school'])->prefix('admin')->name('admin.')->group(function () use ($accountRoutes): void {
    // Dashboard — admins and accountants (finance). Other staff use /staff.
    Route::get('/', [DashboardController::class, 'index'])->middleware('role:admin|accountant')->name('dashboard');
    $accountRoutes();

    // ── Finance + Reports (role: admin OR accountant) ─────────────────────────
    // Spatie multi-role syntax is pipe-separated; a comma is read as the guard.
    Route::middleware('role:admin|accountant')->group(function (): void {
        // Fee categories
        Route::get('/fee-categories', [FeeCategoryController::class, 'index'])->name('fee-categories.index');
        Route::post('/fee-categories', [FeeCategoryController::class, 'store'])->name('fee-categories.store');
        Route::put('/fee-categories/{id}', [FeeCategoryController::class, 'update'])->name('fee-categories.update');
        Route::delete('/fee-categories/{id}', [FeeCategoryController::class, 'destroy'])->name('fee-categories.destroy');

        // Fee items
        Route::get('/fee-items', [FeeItemController::class, 'index'])->name('fee-items.index');
        Route::post('/fee-items', [FeeItemController::class, 'store'])->name('fee-items.store');
        Route::put('/fee-items/{id}', [FeeItemController::class, 'update'])->name('fee-items.update');
        Route::patch('/fee-items/{id}/deactivate', [FeeItemController::class, 'deactivate'])->name('fee-items.deactivate');

        // Fee discounts
        Route::get('/fee-discounts', [FeeDiscountController::class, 'index'])->name('fee-discounts.index');
        Route::post('/fee-discounts', [FeeDiscountController::class, 'store'])->name('fee-discounts.store');
        Route::put('/fee-discounts/{id}', [FeeDiscountController::class, 'update'])->name('fee-discounts.update');
        Route::patch('/fee-discounts/{id}/deactivate', [FeeDiscountController::class, 'deactivate'])->name('fee-discounts.deactivate');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices/generate-single', [InvoiceController::class, 'generateSingle'])->name('invoices.generate-single');
        Route::post('/invoices/generate-bulk', [InvoiceController::class, 'generateBulk'])->name('invoices.generate-bulk');
        Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::patch('/invoices/{id}/waive', [InvoiceController::class, 'waive'])->name('invoices.waive');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/invoices/{invoiceId}/payments', [PaymentController::class, 'store'])->name('payments.store');

        // Refunds
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');

        // Student credit ledger
        Route::get('/student-credit', [StudentCreditController::class, 'index'])->name('student-credit.index');
        Route::post('/student-credit/adjust', [StudentCreditController::class, 'adjust'])->name('student-credit.adjust');

        // Payment config
        Route::get('/payment-config', [PaymentConfigController::class, 'edit'])->name('payment-config.edit');
        Route::put('/payment-config', [PaymentConfigController::class, 'update'])->name('payment-config.update');

        // Reports
        Route::get('/reports/fee-collection', [ReportController::class, 'feeCollection'])->name('reports.fee-collection');
        Route::get('/reports/outstanding-dues', [ReportController::class, 'outstandingDues'])->name('reports.outstanding-dues');
        Route::get('/reports/student-ledger', [ReportController::class, 'studentLedger'])->name('reports.student-ledger');
    });

    // ── Everything else (role: admin) ─────────────────────────────────────────
    Route::middleware('role:admin')->group(function (): void {
        // ── Setup ──────────────────────────────────────────────────────────────
        Route::get('/school', [SchoolController::class, 'edit'])->name('school.edit');
        Route::put('/school', [SchoolController::class, 'update'])->name('school.update');
        Route::put('/school/hours', [SchoolController::class, 'updateHours'])->name('school.hours');

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::put('/modules', [ModuleController::class, 'update'])->name('modules.update');

        // Languages + translations editor
        $lc = LanguageController::class;
        Route::get('/languages', [$lc, 'index'])->name('languages.index');
        Route::post('/languages', [$lc, 'store'])->name('languages.store');
        Route::put('/languages/{id}', [$lc, 'update'])->whereNumber('id')->name('languages.update');
        Route::post('/languages/{id}/default', [$lc, 'setDefault'])->whereNumber('id')->name('languages.default');
        Route::delete('/languages/{id}', [$lc, 'destroy'])->whereNumber('id')->name('languages.destroy');
        Route::post('/languages/scan', [$lc, 'scan'])->name('languages.scan');
        Route::get('/languages/{code}/translations', [$lc, 'translations'])->name('languages.translations');
        Route::put('/languages/{code}/translations', [$lc, 'saveTranslations'])->name('languages.translations.save');

        // Website page builder
        Route::get('/pages', [WebsitePageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [WebsitePageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [WebsitePageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{id}/edit', [WebsitePageController::class, 'edit'])->whereNumber('id')->name('pages.edit');
        Route::put('/pages/{id}', [WebsitePageController::class, 'save'])->whereNumber('id')->name('pages.save');
        Route::post('/pages/{id}/preview', [WebsitePageController::class, 'preview'])->whereNumber('id')->name('pages.preview');
        Route::post('/pages/{id}/preview-block', [WebsitePageController::class, 'previewBlock'])->whereNumber('id')->name('pages.preview-block');
        Route::post('/pages/{id}/homepage', [WebsitePageController::class, 'setHomepage'])->whereNumber('id')->name('pages.homepage');
        Route::post('/pages/{id}/duplicate', [WebsitePageController::class, 'duplicate'])->whereNumber('id')->name('pages.duplicate');
        Route::post('/pages/{id}/save-as-template', [WebsitePageController::class, 'saveAsTemplate'])->whereNumber('id')->name('pages.save-as-template');
        Route::get('/pages/{id}/history', [WebsitePageController::class, 'history'])->whereNumber('id')->name('pages.history');
        Route::post('/pages/{id}/restore/{layoutId}', [WebsitePageController::class, 'restore'])->whereNumber(['id', 'layoutId'])->name('pages.restore');
        Route::delete('/pages/{id}', [WebsitePageController::class, 'destroy'])->whereNumber('id')->name('pages.destroy');

        // Website media library (page editor's Media Library modal)
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::delete('/media/{id}', [MediaController::class, 'destroy'])->whereNumber('id')->name('media.destroy');

        // Navigation menu editor
        Route::get('/menus', [MenuController::class, 'edit'])->name('menus.index');
        Route::put('/menus', [MenuController::class, 'save'])->name('menus.save');

        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::put('/academic-years/{id}', [AcademicYearController::class, 'update'])->name('academic-years.update');
        Route::post('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic-years.set-current');
        Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroy'])->name('academic-years.destroy');

        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
        Route::put('/classes/{id}', [ClassController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{id}', [ClassController::class, 'destroy'])->name('classes.destroy');

        Route::get('/classes/{classId}/sections', [SectionController::class, 'index'])->name('classes.sections.index');
        Route::post('/classes/{classId}/sections', [SectionController::class, 'store'])->name('classes.sections.store');
        Route::put('/classes/{classId}/sections/{id}', [SectionController::class, 'update'])->name('classes.sections.update');
        Route::delete('/classes/{classId}/sections/{id}', [SectionController::class, 'destroy'])->name('classes.sections.destroy');

        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{id}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{id}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        foreach (['groups', 'versions', 'shifts'] as $type) {
            Route::get("/{$type}", [ReferenceController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
            Route::post("/{$type}", [ReferenceController::class, 'store'])->defaults('type', $type)->name("{$type}.store");
            Route::put("/{$type}/{id}", [ReferenceController::class, 'update'])->defaults('type', $type)->name("{$type}.update");
            Route::delete("/{$type}/{id}", [ReferenceController::class, 'destroy'])->defaults('type', $type)->name("{$type}.destroy");
        }

        // Class routine + routine setup (periods/rooms)
        Route::get('/routine', [RoutineController::class, 'index'])->name('routine.index');
        Route::post('/routine', [RoutineController::class, 'store'])->name('routine.store');
        Route::delete('/routine/{id}', [RoutineController::class, 'destroy'])->name('routine.destroy');

        Route::get('/routine-setup', [RoutineSetupController::class, 'index'])->name('routine-setup.index');
        Route::post('/routine-setup/periods', [RoutineSetupController::class, 'storePeriod'])->name('routine-setup.periods.store');
        Route::delete('/routine-setup/periods/{id}', [RoutineSetupController::class, 'destroyPeriod'])->name('routine-setup.periods.destroy');
        Route::post('/routine-setup/rooms', [RoutineSetupController::class, 'storeRoom'])->name('routine-setup.rooms.store');
        Route::delete('/routine-setup/rooms/{id}', [RoutineSetupController::class, 'destroyRoom'])->name('routine-setup.rooms.destroy');

        // ── People ─────────────────────────────────────────────────────────────
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{id}', [StudentController::class, 'show'])->name('students.show');
        Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('/students/{id}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');
        Route::patch('/students/{id}/transfer', [StudentController::class, 'transfer'])->name('students.transfer');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::patch('/staff/{id}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');

        foreach (['designations', 'departments'] as $type) {
            Route::get("/{$type}", [StaffReferenceController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
            Route::post("/{$type}", [StaffReferenceController::class, 'store'])->defaults('type', $type)->name("{$type}.store");
            Route::put("/{$type}/{id}", [StaffReferenceController::class, 'update'])->defaults('type', $type)->name("{$type}.update");
            Route::delete("/{$type}/{id}", [StaffReferenceController::class, 'destroy'])->defaults('type', $type)->name("{$type}.destroy");
        }

        // Online admission applications
        Route::get('/admissions', [AdmissionController::class, 'index'])->name('admissions.index');
        Route::get('/admissions/{id}', [AdmissionController::class, 'show'])->name('admissions.show');
        Route::patch('/admissions/{id}/approve', [AdmissionController::class, 'approve'])->name('admissions.approve');
        Route::patch('/admissions/{id}/reject', [AdmissionController::class, 'reject'])->name('admissions.reject');

        // Data import (student/staff bulk upload)
        Route::get('/data-import', [DataImportController::class, 'index'])->name('data-import.index');
        Route::post('/data-import', [DataImportController::class, 'store'])->name('data-import.store');
        Route::get('/data-import/{id}', [DataImportController::class, 'show'])->name('data-import.show');

        // Certificates: testimonial templates, testimonials, admit cards
        Route::get('/cert-templates', [CertTemplateController::class, 'index'])->name('cert-templates.index');
        Route::post('/cert-templates', [CertTemplateController::class, 'store'])->name('cert-templates.store');
        Route::put('/cert-templates/{id}', [CertTemplateController::class, 'update'])->name('cert-templates.update');
        Route::delete('/cert-templates/{id}', [CertTemplateController::class, 'destroy'])->name('cert-templates.destroy');

        Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
        Route::post('/testimonials', [TestimonialController::class, 'store'])->name('testimonials.store');
        Route::get('/testimonials/{id}/download', [TestimonialController::class, 'download'])->name('testimonials.download');

        Route::get('/admit-cards', [AdmitCardController::class, 'index'])->name('admit-cards.index');
        Route::post('/admit-cards', [AdmitCardController::class, 'store'])->name('admit-cards.store');
        Route::get('/admit-cards/{id}/download', [AdmitCardController::class, 'download'])->name('admit-cards.download');

        // ID card templates + batches (queued generation)
        Route::get('/id-card-templates', [IdCardTemplateController::class, 'index'])->name('id-card-templates.index');
        Route::post('/id-card-templates', [IdCardTemplateController::class, 'store'])->name('id-card-templates.store');
        Route::put('/id-card-templates/{id}', [IdCardTemplateController::class, 'update'])->name('id-card-templates.update');
        Route::delete('/id-card-templates/{id}', [IdCardTemplateController::class, 'destroy'])->name('id-card-templates.destroy');

        Route::get('/id-cards', [IdCardBatchController::class, 'index'])->name('id-cards.index');
        Route::post('/id-cards', [IdCardBatchController::class, 'store'])->name('id-cards.store');
        Route::get('/id-cards/{id}', [IdCardBatchController::class, 'show'])->name('id-cards.show');
        Route::get('/id-cards/{id}/files/{fileId}/download', [IdCardBatchController::class, 'download'])->name('id-cards.download');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{id}/role', [UserController::class, 'changeRole'])->name('users.change-role');
        Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

        // ── Academics ──────────────────────────────────────────────────────────
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        Route::get('/exam-types', [ExamTypeController::class, 'index'])->name('exam-types.index');
        Route::post('/exam-types', [ExamTypeController::class, 'store'])->name('exam-types.store');
        Route::put('/exam-types/{id}', [ExamTypeController::class, 'update'])->name('exam-types.update');
        Route::delete('/exam-types/{id}', [ExamTypeController::class, 'destroy'])->name('exam-types.destroy');

        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{id}', [ExamController::class, 'show'])->name('exams.show');
        Route::patch('/exams/{id}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::patch('/exams/{id}/complete', [ExamController::class, 'complete'])->name('exams.complete');
        Route::post('/exams/{id}/subjects', [ExamController::class, 'addSubject'])->name('exams.subjects.store');
        Route::delete('/exams/{id}/subjects/{subjectId}', [ExamController::class, 'removeSubject'])->name('exams.subjects.destroy');

        Route::get('/mark-settings', [MarkSettingController::class, 'index'])->name('mark-settings.index');
        Route::put('/mark-settings/{classId}', [MarkSettingController::class, 'update'])->name('mark-settings.update');
        Route::post('/mark-settings/{classId}/grade-template', [MarkSettingController::class, 'applyTemplate'])->name('mark-settings.apply-template');

        Route::get('/exams/{examId}/marks', [ExamMarkController::class, 'index'])->name('exam-marks.index');
        Route::post('/exams/{examId}/marks/divisions', [ExamMarkController::class, 'storeDivision'])->name('exam-marks.divisions.store');
        Route::delete('/exams/{examId}/marks/divisions/{divisionId}', [ExamMarkController::class, 'destroyDivision'])->name('exam-marks.divisions.destroy');
        Route::get('/exams/{examId}/marks/divisions/{divisionId}/entry', [ExamMarkController::class, 'entry'])->name('exam-marks.entry');
        Route::post('/exams/{examId}/marks/divisions/{divisionId}/entry', [ExamMarkController::class, 'saveEntry'])->name('exam-marks.entry.save');
        Route::post('/exams/{examId}/marks/calculate', [ExamMarkController::class, 'calculate'])->name('exam-marks.calculate');
        Route::patch('/exams/{examId}/marks/lock', [ExamMarkController::class, 'lock'])->name('exam-marks.lock');
        Route::get('/exams/{examId}/marks/results', [ExamMarkController::class, 'results'])->name('exam-marks.results');

        Route::get('/exam-halls', [HallController::class, 'index'])->name('exam-halls.index');
        Route::post('/exam-halls', [HallController::class, 'store'])->name('exam-halls.store');
        Route::get('/exam-halls/{id}', [HallController::class, 'show'])->name('exam-halls.show');
        Route::put('/exam-halls/{id}', [HallController::class, 'regenerate'])->name('exam-halls.regenerate');
        Route::patch('/exam-halls/{id}/seats/{seatId}/toggle', [HallController::class, 'toggleSeat'])->name('exam-halls.seats.toggle');
        Route::delete('/exam-halls/{id}', [HallController::class, 'destroy'])->name('exam-halls.destroy');

        Route::get('/exams/{examId}/seating', [ExamSeatingController::class, 'index'])->name('exam-seating.index');
        Route::post('/exams/{examId}/seating', [ExamSeatingController::class, 'assign'])->name('exam-seating.assign');
        Route::delete('/exams/{examId}/seating', [ExamSeatingController::class, 'clear'])->name('exam-seating.clear');

        // ── Comms ──────────────────────────────────────────────────────────────
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::patch('/announcements/{id}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::patch('/announcements/{id}/expire', [AnnouncementController::class, 'expire'])->name('announcements.expire');
        Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
        Route::post('/sms', [SmsController::class, 'store'])->name('sms.store');
        Route::get('/sms/{id}', [SmsController::class, 'show'])->name('sms.show');

        // Contact-form enquiries (public contact block → admin inbox)
        Route::get('/enquiries', [ContactMessageController::class, 'index'])->name('enquiries.index');
        Route::patch('/enquiries/{id}/read', [ContactMessageController::class, 'markRead'])->whereNumber('id')->name('enquiries.read');
        Route::delete('/enquiries/{id}', [ContactMessageController::class, 'destroy'])->whereNumber('id')->name('enquiries.destroy');

        // Messaging (admin = staff participant + oversight)
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/all', [MessageController::class, 'all'])->name('messages.all');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/{id}', [MessageController::class, 'show'])->whereNumber('id')->name('messages.show');
        Route::post('/messages/{id}/reply', [MessageController::class, 'reply'])->whereNumber('id')->name('messages.reply');
        Route::patch('/messages/{id}/lock', [MessageController::class, 'lock'])->whereNumber('id')->name('messages.lock');

        // ── HR: Leave ──────────────────────────────────────────────────────────
        Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('/leave-types/{id}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');

        Route::get('/student-leave', [StudentLeaveController::class, 'index'])->name('student-leave.index');
        Route::patch('/student-leave/{id}/approve', [StudentLeaveController::class, 'approve'])->name('student-leave.approve');
        Route::patch('/student-leave/{id}/reject', [StudentLeaveController::class, 'reject'])->name('student-leave.reject');
        Route::patch('/student-leave/{id}/cancel', [StudentLeaveController::class, 'cancel'])->name('student-leave.cancel');

        Route::get('/staff-leave', [StaffLeaveController::class, 'index'])->name('staff-leave.index');
        Route::patch('/staff-leave/{id}/approve', [StaffLeaveController::class, 'approve'])->name('staff-leave.approve');
        Route::patch('/staff-leave/{id}/reject', [StaffLeaveController::class, 'reject'])->name('staff-leave.reject');
        Route::patch('/staff-leave/{id}/cancel', [StaffLeaveController::class, 'cancel'])->name('staff-leave.cancel');

        // ── HR: Staff loans ──────────────────────────────────────────────────────
        Route::get('/staff-loans', [StaffLoanController::class, 'index'])->name('staff-loans.index');
        Route::post('/staff-loans', [StaffLoanController::class, 'store'])->name('staff-loans.store');
        Route::get('/staff-loans/{id}', [StaffLoanController::class, 'show'])->name('staff-loans.show');
        Route::patch('/staff-loans/{id}/approve', [StaffLoanController::class, 'approve'])->name('staff-loans.approve');
        Route::patch('/staff-loans/{id}/reject', [StaffLoanController::class, 'reject'])->name('staff-loans.reject');
        Route::patch('/staff-loans/{id}/cancel', [StaffLoanController::class, 'cancel'])->name('staff-loans.cancel');

        // ── Optional modules (gated by module.enabled) ─────────────────────────
        Route::middleware('module.enabled:library')->prefix('library')->name('library.')->group(function (): void {
            Route::get('/books', [BookController::class, 'index'])->name('books.index');
            Route::post('/books', [BookController::class, 'store'])->name('books.store');
            Route::put('/books/{id}', [BookController::class, 'update'])->name('books.update');
            Route::patch('/books/{id}/deactivate', [BookController::class, 'deactivate'])->name('books.deactivate');

            Route::get('/members', [MemberController::class, 'index'])->name('members.index');
            Route::post('/members', [MemberController::class, 'store'])->name('members.store');
            Route::put('/members/{id}', [MemberController::class, 'update'])->name('members.update');
            Route::patch('/members/{id}/deactivate', [MemberController::class, 'deactivate'])->name('members.deactivate');

            Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');
            Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store');
            Route::patch('/borrow/{id}/return', [BorrowController::class, 'markReturned'])->name('borrow.return');
        });

        Route::middleware('module.enabled:transport')->prefix('transport')->name('transport.')->group(function (): void {
            Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
            Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
            Route::put('/drivers/{id}', [DriverController::class, 'update'])->name('drivers.update');

            Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
            Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
            Route::put('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update');

            Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
            Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
            Route::put('/routes/{id}', [RouteController::class, 'update'])->name('routes.update');
            Route::get('/routes/{id}', [RouteController::class, 'show'])->name('routes.show');
            Route::patch('/routes/{id}/vehicle', [RouteController::class, 'setVehicle'])->name('routes.set-vehicle');
            Route::post('/routes/{id}/riders', [RouteController::class, 'assign'])->name('routes.riders.assign');
            Route::patch('/routes/{id}/riders/{assignmentId}/end', [RouteController::class, 'endAssignment'])->name('routes.riders.end');
        });

        Route::middleware('module.enabled:payroll')->prefix('payroll')->name('payroll.')->group(function (): void {
            Route::get('/components', [SalaryComponentController::class, 'index'])->name('components.index');
            Route::post('/components', [SalaryComponentController::class, 'store'])->name('components.store');
            Route::put('/components/{id}', [SalaryComponentController::class, 'update'])->name('components.update');
            Route::delete('/components/{id}', [SalaryComponentController::class, 'destroy'])->name('components.destroy');

            Route::get('/staff-salaries', [StaffSalaryController::class, 'index'])->name('staff-salaries.index');
            Route::get('/staff-salaries/{staffId}', [StaffSalaryController::class, 'edit'])->name('staff-salaries.edit');
            Route::put('/staff-salaries/{staffId}', [StaffSalaryController::class, 'update'])->name('staff-salaries.update');

            Route::get('/runs', [PayrollRunController::class, 'index'])->name('runs.index');
            Route::post('/runs', [PayrollRunController::class, 'store'])->name('runs.store');
            Route::get('/runs/{id}', [PayrollRunController::class, 'show'])->name('runs.show');
            Route::patch('/runs/{id}/process', [PayrollRunController::class, 'process'])->name('runs.process');
            Route::patch('/runs/{id}/approve', [PayrollRunController::class, 'approve'])->name('runs.approve');
        });

        Route::middleware('module.enabled:lms')->prefix('lms')->name('lms.')->group(function (): void {
            Route::get('/courses', [LmsCourseController::class, 'index'])->name('courses.index');
            Route::post('/courses', [LmsCourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{id}', [LmsCourseController::class, 'show'])->name('courses.show');
            Route::put('/courses/{id}', [LmsCourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{id}', [LmsCourseController::class, 'destroy'])->name('courses.destroy');

            Route::post('/courses/{courseId}/lessons', [LmsLessonController::class, 'store'])->name('courses.lessons.store');
            Route::patch('/courses/{courseId}/lessons/{lessonId}/publish', [LmsLessonController::class, 'publish'])->name('courses.lessons.publish');
            Route::delete('/courses/{courseId}/lessons/{lessonId}', [LmsLessonController::class, 'destroy'])->name('courses.lessons.destroy');

            Route::post('/courses/{courseId}/assignments', [LmsAssignmentController::class, 'store'])->name('courses.assignments.store');
            Route::get('/courses/{courseId}/assignments/{assignmentId}', [LmsAssignmentController::class, 'show'])->name('courses.assignments.show');
            Route::delete('/courses/{courseId}/assignments/{assignmentId}', [LmsAssignmentController::class, 'destroy'])->name('courses.assignments.destroy');

            Route::patch('/submissions/{id}/grade', [LmsSubmissionController::class, 'grade'])->name('submissions.grade');
        });
    });
});

// Public website pages by slug — registered LAST so the admin/login/home routes
// above always win. Single path segment only (no slashes); reserved slugs like
// "admin"/"login" are already blocked at page-creation time (PageService).
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('page.show');
