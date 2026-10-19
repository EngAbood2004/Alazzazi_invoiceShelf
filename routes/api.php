<?php

use App\Http\Controllers\CommunicationWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', [CommunicationWebhookController::class, 'verifyWhatsApp']);
Route::post('/webhooks/whatsapp', [CommunicationWebhookController::class, 'receiveWhatsApp']);
Route::post('/webhooks/twilio/inbound', [CommunicationWebhookController::class, 'receiveTwilioSms']);
Route::post('/webhooks/twilio/status', [CommunicationWebhookController::class, 'updateTwilioStatus']);
