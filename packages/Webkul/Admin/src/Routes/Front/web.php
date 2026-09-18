<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\ConsentPortalController;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * Home routes.
 */
Route::get('/', [Controller::class, 'redirectToLogin'])->name('krayin.home');

/**
 * CMS Digital Consent Public Mobile Portal Routes (45 CFR § 155.220)
 */
Route::controller(ConsentPortalController::class)->prefix('consent')->group(function () {
    Route::get('{token}', 'show')->name('consent.portal.show');
    Route::post('{token}', 'sign')->name('consent.portal.sign');
    Route::get('{token}/receipt', 'receipt')->name('consent.portal.receipt');
});
