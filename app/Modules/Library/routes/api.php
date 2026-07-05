<?php

use App\Modules\Library\Http\Controllers\BookController;
use App\Modules\Library\Http\Controllers\BorrowRecordController;
use App\Modules\Library\Http\Controllers\LibraryMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:admin:*,librarian:*', 'module.enabled:library'])
    ->prefix('v2/library')
    ->group(function (): void {
        // Books
        Route::get('/books', [BookController::class, 'index']);
        Route::post('/books', [BookController::class, 'store']);
        Route::get('/books/{id}', [BookController::class, 'show'])->whereNumber('id');
        Route::put('/books/{id}', [BookController::class, 'update'])->whereNumber('id');
        Route::delete('/books/{id}', [BookController::class, 'destroy'])->whereNumber('id');

        // Members
        Route::get('/members', [LibraryMemberController::class, 'index']);
        Route::post('/members', [LibraryMemberController::class, 'store']);
        Route::get('/members/{id}', [LibraryMemberController::class, 'show'])->whereNumber('id');
        Route::put('/members/{id}', [LibraryMemberController::class, 'update'])->whereNumber('id');
        Route::post('/members/{id}/deactivate', [LibraryMemberController::class, 'deactivate'])->whereNumber('id');

        // Borrow records
        Route::get('/borrow-records', [BorrowRecordController::class, 'index']);
        Route::get('/borrow-records/{id}', [BorrowRecordController::class, 'show'])->whereNumber('id');
        Route::post('/borrow-records', [BorrowRecordController::class, 'store']);
        Route::post('/borrow-records/{id}/return', [BorrowRecordController::class, 'returnBorrow'])->whereNumber('id');
    });
