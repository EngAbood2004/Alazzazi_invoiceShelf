<?php

use App\Http\Controllers\CommunicationWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', [CommunicationWebhookController::class, 'verifyWhatsApp']);
Route::post('/webhooks/whatsapp', [CommunicationWebhookController::class, 'receiveWhatsApp']);
Route::post('/webhooks/lahza', [\App\Http\Controllers\PublicArea\PublicInvoiceController::class, 'webhook'])->name('webhooks.lahza');
Route::post('/webhooks/twilio/inbound', [CommunicationWebhookController::class, 'receiveTwilioSms']);
Route::post('/webhooks/twilio/status', [CommunicationWebhookController::class, 'updateTwilioStatus']);

// Mobile app / REST API v1 (Bearer token)
Route::prefix('v1')->name('api.')->group(function () {
    Route::post('auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware(['auth.token', 'throttle:120,1'])->group(function () {
        Route::get('auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->name('logout');

        Route::middleware('role:accountant')->controller(\App\Http\Controllers\Api\V1\InvoicingApiController::class)->group(function () {
            Route::get('dashboard', 'dashboard')->name('dashboard');
            Route::get('lookups', 'lookups')->name('lookups');
            Route::get('customers', 'customers')->name('customers.index');
            Route::post('customers', 'storeCustomer')->name('customers.store');
            Route::get('customers/{customer}', 'customer')->name('customers.show');
            Route::get('invoices', 'index')->name('invoices.index');
            Route::post('invoices', 'store')->name('invoices.store');
            Route::get('invoices/{invoice}', 'show')->name('invoices.show');
            Route::post('invoices/{invoice}/send', 'send')->name('invoices.send');
            Route::post('invoices/{invoice}/payments', 'recordPayment')->name('invoices.payments.store');
            Route::post('invoices/{invoice}/remind', 'remind')->middleware('throttle:10,1')->name('invoices.remind');
        });
    });
});
