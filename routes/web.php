<?php


oginController;
use App\Http\Controllers\Admin\DashboardController;
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
});
