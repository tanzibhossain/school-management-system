<?php

use App\Http\Controllers\Admin\Academics\AttendanceController;
use App\Http\Controllers\Admin\Academics\ExamController;
use App\Http\Controllers\Admin\Academics\ExamMarkController;
use App\Http\Controllers\Admin\Academics\ExamSeatingController;
use App\Http\Controllers\Admin\Academics\ExamTypeController;
use App\Http\Controllers\Admin\Academics\HallController;
use App\Http\Controllers\Admin\Academics\MarkSettingController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Comms\AnnouncementController;
use App\Http\Controllers\Admin\Comms\ReportController;
use App\Http\Controllers\Admin\Comms\SmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Modules\Library\BookController;
use App\Http\Controllers\Admin\Modules\Library\BorrowController;
use App\Http\Controllers\Admin\Modules\Library\MemberController;
use App\Http\Controllers\Admin\Modules\Transport\DriverController;
use App\Http\Controllers\Admin\Modules\Transport\RouteController;
use App\Http\Controllers\Admin\Modules\Transport\VehicleController;
use App\Http\Controllers\Admin\Finance\FeeCategoryController;
use App\Http\Controllers\Admin\Finance\FeeDiscountController;
use App\Http\Controllers\Admin\Finance\FeeItemController;
use App\Http\Controllers\Admin\Finance\InvoiceController;
use App\Http\Controllers\Admin\Finance\PaymentConfigController;
use App\Http\Controllers\Admin\Finance\PaymentController;
use App\Http\Controllers\Admin\Finance\RefundController;
use App\Http\Controllers\Admin\People\StaffController;
use App\Http\Controllers\Admin\People\StaffReferenceController;
use App\Http\Controllers\Admin\People\StudentController;
use App\Http\Controllers\Admin\People\UserController;
use App\Http\Controllers\Admin\Setup\AcademicYearController;
use App\Http\Controllers\Admin\Setup\ClassController;
use App\Http\Controllers\Admin\Setup\ModuleController;
use App\Http\Controllers\Admin\Setup\ReferenceController;
use App\Http\Controllers\Admin\Setup\SchoolController;
use App\Http\Controllers\Admin\Setup\SectionController;
use App\Http\Controllers\Admin\Setup\SubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('admin.dashboard'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'school'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Setup ────────────────────────────────────────────────────────────────
    // School settings
    Route::get('/school', [SchoolController::class, 'edit'])->name('school.edit');
    Route::put('/school', [SchoolController::class, 'update'])->name('school.update');

    // Module toggles
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::put('/modules', [ModuleController::class, 'update'])->name('modules.update');

    // Academic years
    Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
    Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
    Route::put('/academic-years/{id}', [AcademicYearController::class, 'update'])->name('academic-years.update');
    Route::post('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic-years.set-current');
    Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroy'])->name('academic-years.destroy');

    // Classes
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::put('/classes/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Sections (nested under a class)
    Route::get('/classes/{classId}/sections', [SectionController::class, 'index'])->name('classes.sections.index');
    Route::post('/classes/{classId}/sections', [SectionController::class, 'store'])->name('classes.sections.store');
    Route::put('/classes/{classId}/sections/{id}', [SectionController::class, 'update'])->name('classes.sections.update');
    Route::delete('/classes/{classId}/sections/{id}', [SectionController::class, 'destroy'])->name('classes.sections.destroy');

    // Subjects
    Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::put('/subjects/{id}', [SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

    // Reference lists — groups / versions / shifts (one controller, {type} via defaults)
    foreach (['groups', 'versions', 'shifts'] as $type) {
        Route::get("/{$type}", [ReferenceController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
        Route::post("/{$type}", [ReferenceController::class, 'store'])->defaults('type', $type)->name("{$type}.store");
        Route::put("/{$type}/{id}", [ReferenceController::class, 'update'])->defaults('type', $type)->name("{$type}.update");
        Route::delete("/{$type}/{id}", [ReferenceController::class, 'destroy'])->defaults('type', $type)->name("{$type}.destroy");
    }

    // ── People ───────────────────────────────────────────────────────────────
    // Students
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
    Route::patch('/students/{id}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');

    // Staff
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
    Route::patch('/staff/{id}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');

    // Designations / Departments (one controller, {type} via defaults)
    foreach (['designations', 'departments'] as $type) {
        Route::get("/{$type}", [StaffReferenceController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
        Route::post("/{$type}", [StaffReferenceController::class, 'store'])->defaults('type', $type)->name("{$type}.store");
        Route::put("/{$type}/{id}", [StaffReferenceController::class, 'update'])->defaults('type', $type)->name("{$type}.update");
        Route::delete("/{$type}/{id}", [StaffReferenceController::class, 'destroy'])->defaults('type', $type)->name("{$type}.destroy");
    }

    // Users & roles
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{id}/role', [UserController::class, 'changeRole'])->name('users.change-role');
    Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

    // ── Finance ──────────────────────────────────────────────────────────────
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

    // Payment config
    Route::get('/payment-config', [PaymentConfigController::class, 'edit'])->name('payment-config.edit');
    Route::put('/payment-config', [PaymentConfigController::class, 'update'])->name('payment-config.update');

    // ── Academics ────────────────────────────────────────────────────────────
    // Attendance register
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // Exam types
    Route::get('/exam-types', [ExamTypeController::class, 'index'])->name('exam-types.index');
    Route::post('/exam-types', [ExamTypeController::class, 'store'])->name('exam-types.store');
    Route::put('/exam-types/{id}', [ExamTypeController::class, 'update'])->name('exam-types.update');
    Route::delete('/exam-types/{id}', [ExamTypeController::class, 'destroy'])->name('exam-types.destroy');

    // Exams
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{id}', [ExamController::class, 'show'])->name('exams.show');
    Route::patch('/exams/{id}/publish', [ExamController::class, 'publish'])->name('exams.publish');
    Route::patch('/exams/{id}/complete', [ExamController::class, 'complete'])->name('exams.complete');
    Route::post('/exams/{id}/subjects', [ExamController::class, 'addSubject'])->name('exams.subjects.store');
    Route::delete('/exams/{id}/subjects/{subjectId}', [ExamController::class, 'removeSubject'])->name('exams.subjects.destroy');

    // Mark settings (per class) + grade templates
    Route::get('/mark-settings', [MarkSettingController::class, 'index'])->name('mark-settings.index');
    Route::put('/mark-settings/{classId}', [MarkSettingController::class, 'update'])->name('mark-settings.update');
    Route::post('/mark-settings/{classId}/grade-template', [MarkSettingController::class, 'applyTemplate'])->name('mark-settings.apply-template');

    // Exam marks — divisions, entry, calculate, lock, tabulation
    Route::get('/exams/{examId}/marks', [ExamMarkController::class, 'index'])->name('exam-marks.index');
    Route::post('/exams/{examId}/marks/divisions', [ExamMarkController::class, 'storeDivision'])->name('exam-marks.divisions.store');
    Route::delete('/exams/{examId}/marks/divisions/{divisionId}', [ExamMarkController::class, 'destroyDivision'])->name('exam-marks.divisions.destroy');
    Route::get('/exams/{examId}/marks/divisions/{divisionId}/entry', [ExamMarkController::class, 'entry'])->name('exam-marks.entry');
    Route::post('/exams/{examId}/marks/divisions/{divisionId}/entry', [ExamMarkController::class, 'saveEntry'])->name('exam-marks.entry.save');
    Route::post('/exams/{examId}/marks/calculate', [ExamMarkController::class, 'calculate'])->name('exam-marks.calculate');
    Route::patch('/exams/{examId}/marks/lock', [ExamMarkController::class, 'lock'])->name('exam-marks.lock');
    Route::get('/exams/{examId}/marks/results', [ExamMarkController::class, 'results'])->name('exam-marks.results');

    // Exam halls + seat map
    Route::get('/exam-halls', [HallController::class, 'index'])->name('exam-halls.index');
    Route::post('/exam-halls', [HallController::class, 'store'])->name('exam-halls.store');
    Route::get('/exam-halls/{id}', [HallController::class, 'show'])->name('exam-halls.show');
    Route::put('/exam-halls/{id}', [HallController::class, 'regenerate'])->name('exam-halls.regenerate');
    Route::patch('/exam-halls/{id}/seats/{seatId}/toggle', [HallController::class, 'toggleSeat'])->name('exam-halls.seats.toggle');
    Route::delete('/exam-halls/{id}', [HallController::class, 'destroy'])->name('exam-halls.destroy');

    // Per-exam seat assignment
    Route::get('/exams/{examId}/seating', [ExamSeatingController::class, 'index'])->name('exam-seating.index');
    Route::post('/exams/{examId}/seating', [ExamSeatingController::class, 'assign'])->name('exam-seating.assign');
    Route::delete('/exams/{examId}/seating', [ExamSeatingController::class, 'clear'])->name('exam-seating.clear');

    // ── Comms & reports ──────────────────────────────────────────────────────
    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('/announcements/{id}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::patch('/announcements/{id}/expire', [AnnouncementController::class, 'expire'])->name('announcements.expire');
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // SMS
    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::post('/sms', [SmsController::class, 'store'])->name('sms.store');
    Route::get('/sms/{id}', [SmsController::class, 'show'])->name('sms.show');

    // Reports
    Route::get('/reports/fee-collection', [ReportController::class, 'feeCollection'])->name('reports.fee-collection');
    Route::get('/reports/outstanding-dues', [ReportController::class, 'outstandingDues'])->name('reports.outstanding-dues');
    Route::get('/reports/student-ledger', [ReportController::class, 'studentLedger'])->name('reports.student-ledger');

    // ── Optional modules (gated by module.enabled) ───────────────────────────
    // Library
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

    // Transport
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
});
