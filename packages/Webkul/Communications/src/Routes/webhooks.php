<?php

use Illuminate\Support\Facades\Route;
use Webkul\Communications\Http\Controllers\ChatwootWebhookController;

/**
 * Chatwoot events. Same URL as the earlier integration, now handled here;
 * no session or CSRF (the URL carries its own secret token).
 */
Route::post('api/chatwoot/webhook', [ChatwootWebhookController::class, 'handle'])
    ->middleware('throttle:300,1')
    ->name('communications.chatwoot.webhook');
