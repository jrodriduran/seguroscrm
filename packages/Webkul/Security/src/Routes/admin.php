<?php

use Illuminate\Support\Facades\Route;
use Webkul\Security\Http\Controllers\AccessLogController;
use Webkul\Security\Http\Controllers\TwoFactorController;
use Webkul\Security\Http\Controllers\TwoFactorUserController;

/**
 * Team administration (ACL protected).
 */
Route::prefix('settings/security')->group(function () {
    Route::get('access-logs', [AccessLogController::class, 'index'])->name('admin.settings.security.access_logs.index');

    Route::get('two-factor', [TwoFactorUserController::class, 'index'])->name('admin.settings.security.two_factor.index');

    Route::delete('two-factor/{id}', [TwoFactorUserController::class, 'reset'])->name('admin.settings.security.two_factor.reset');
});

/**
 * Every signed-in user: their own two-factor. Reachable before the challenge is passed.
 */
Route::controller(TwoFactorController::class)->group(function () {
    Route::get('two-factor/challenge', 'challenge')->name('admin.security.two_factor.challenge');

    Route::post('two-factor/challenge', 'verifyChallenge')->middleware('throttle:20,1')->name('admin.security.two_factor.challenge.verify');

    Route::prefix('account/security')->group(function () {
        Route::get('', 'setup')->name('admin.security.two_factor.setup');

        Route::post('confirm', 'confirm')->middleware('throttle:10,1')->name('admin.security.two_factor.confirm');

        Route::post('recovery-codes', 'regenerateCodes')->middleware('throttle:10,1')->name('admin.security.two_factor.recovery_codes');

        Route::post('disable', 'disable')->middleware('throttle:10,1')->name('admin.security.two_factor.disable');

        Route::post('forget-devices', 'forgetDevices')->name('admin.security.two_factor.forget_devices');
    });
});
