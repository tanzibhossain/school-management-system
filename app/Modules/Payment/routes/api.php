<?php

use App\Modules\Payment\Http\Controllers\BkashWebhookController;
use App\Modules\Payment\Http\Controllers\ChequeController;
use App\Modules\Payment\Http\Controllers\CreditController;
use App\Modules\Payment\Http\Controllers\InvoiceController;
use App\Modules\Payment\Http\Controllers\PaymentConfigController;
use App\Modules\Payment\Http\Controllers\PaymentController;
use App\Modules\Payment\Http\Controllers\RefundController;
use App\Modules\Payment\Http\Controllers\SslcommerzWebhookController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────
// Payment configuration (admin)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/payment-config')
    ->group(function (): void {
        Route::get('/', [PaymentConfigController::class, 'show']);
        Route::put('/', [PaymentConfigController::class, 'update']);
    });

// ──────────────────────────────────────────────
// Invoices (admin)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/invoices')
    ->group(function (): void {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/generate', [InvoiceController::class, 'generate']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::post('/{id}/cancel', [InvoiceController::class, 'cancel']);
        Route::post('/{id}/waive', [InvoiceController::class, 'waive']);
    });

// Portal: student's own invoices
Route::middleware(['auth:sanctum', 'ability:student:*'])
    ->prefix('v2/my-invoices')
    ->group(function (): void {
        Route::get('/', [InvoiceController::class, 'myInvoices']);
    });

// ──────────────────────────────────────────────
// Payments (admin)
// Literal /invoices/... routes MUST come before /{id} to avoid wildcard capture.
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/payments')
    ->group(function (): void {
        Route::post('/invoices/{invoiceId}/record', [PaymentController::class, 'record']);
        Route::post('/invoices/{invoiceId}/bkash/initiate', [PaymentController::class, 'initiateBkash']);
        Route::post('/invoices/{invoiceId}/sslcommerz/initiate', [PaymentController::class, 'initiateSslcommerz']);
        Route::get('/{id}', [PaymentController::class, 'show']);   // ← after literals
    });

// ──────────────────────────────────────────────
// Cheque management (admin)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/cheques')
    ->group(function (): void {
        Route::get('/', [ChequeController::class, 'index']);
        Route::post('/{id}/clear', [ChequeController::class, 'clear']);
        Route::post('/{id}/bounce', [ChequeController::class, 'bounce']);
    });

// ──────────────────────────────────────────────
// Refunds (admin)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/refunds')
    ->group(function (): void {
        Route::get('/', [RefundController::class, 'index']);
        Route::post('/payments/{paymentId}', [RefundController::class, 'request']); // ← before /{id}
        Route::get('/{id}', [RefundController::class, 'show']);
    });

// ──────────────────────────────────────────────
// Student credit (admin)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])
    ->prefix('v2/credits')
    ->group(function (): void {
        Route::get('/students/{studentId}', [CreditController::class, 'balance']);
        Route::get('/students/{studentId}/transactions', [CreditController::class, 'transactions']);
    });

// ──────────────────────────────────────────────
// Gateway webhooks / callbacks (unauthenticated)
// ──────────────────────────────────────────────

// bKash — browser GET redirect after payment
Route::get('v2/payments/bkash/callback', [BkashWebhookController::class, 'callback'])
    ->name('payment.bkash.callback');

// SSLCommerz — browser redirects + server IPN
Route::post('v2/payments/sslcommerz/ipn', [SslcommerzWebhookController::class, 'ipn'])
    ->name('payment.sslcommerz.ipn');
Route::match(['get', 'post'], 'v2/payments/sslcommerz/success', [SslcommerzWebhookController::class, 'success'])
    ->name('payment.sslcommerz.success');
Route::match(['get', 'post'], 'v2/payments/sslcommerz/fail', [SslcommerzWebhookController::class, 'fail'])
    ->name('payment.sslcommerz.fail');
Route::match(['get', 'post'], 'v2/payments/sslcommerz/cancel', [SslcommerzWebhookController::class, 'cancel'])
    ->name('payment.sslcommerz.cancel');
