<?php

use Illuminate\Support\Facades\Route;
use Webkul\Platform\Http\Controllers\PlatformApiController;
use Webkul\Platform\Http\Controllers\SupportLoginController;

/**
 * Operator panel API: no session, HMAC-signed, rate limited.
 */
Route::prefix('platform/api')->middleware('throttle:60,1')->controller(PlatformApiController::class)->group(function () {
    Route::get('health', 'health')->name('platform.api.health');

    Route::post('status', 'status')->name('platform.api.status');

    Route::post('support-link', 'supportLink')->name('platform.api.support_link');

    Route::post('reset-owner', 'resetOwner')->name('platform.api.reset_owner');
});

/**
 * One-time support login (needs the web session).
 */
Route::middleware(['web', 'throttle:20,1'])
    ->get(trim(config('app.admin_path'), '/').'/platform/support/{token}', SupportLoginController::class)
    ->name('platform.support.login');
