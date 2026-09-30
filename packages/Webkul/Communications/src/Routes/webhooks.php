<?php

use Illuminate\Support\Facades\Route;
use Webkul\Communications\Http\Controllers\ChatwootWebhookController;
use Webkul\Communications\Http\Controllers\UnsubscribeController;

/**
 * Chatwoot events. Same URL as the earlier integration, now handled here;
 * no session or CSRF (the URL carries its own secret token).
 */
Route::post('api/chatwoot/webhook', [ChatwootWebhookController::class, 'handle'])
    ->middleware('throttle:300,1')
    ->name('communications.chatwoot.webhook');

/**
 * Signed unsubscribe link in marketing emails: no login, and the one-click
 * POST from mail apps works without a CSRF token.
 */
Route::middleware(['signed', 'throttle:30,1'])->group(function () {
    Route::get('communications/unsubscribe/{person}', [UnsubscribeController::class, 'show'])->name('communications.unsubscribe');

    Route::post('communications/unsubscribe/{person}', [UnsubscribeController::class, 'confirm'])->name('communications.unsubscribe.confirm');
});
