<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
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
});
