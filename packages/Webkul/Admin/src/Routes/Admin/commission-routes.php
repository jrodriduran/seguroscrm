<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Insurance\CommissionController;

Route::group(['middleware' => ['web', 'admin_locale', 'user']], function () {
    Route::controller(CommissionController::class)->prefix('commissions')->group(function () {
        Route::get('', 'index')->name('admin.commissions.index');
        Route::post('', 'store')->name('admin.commissions.store');
        Route::put('{id}', 'update')->name('admin.commissions.update');
        Route::delete('{id}', 'destroy')->name('admin.commissions.destroy');
        Route::put('rates/{id}', 'updateRate')->name('admin.commissions.rates.update');
    });
});
