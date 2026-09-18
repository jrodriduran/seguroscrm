<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Insurance\BookOfBusinessController;
use Webkul\Admin\Http\Controllers\Insurance\ExecutiveAnalyticsController;

Route::controller(BookOfBusinessController::class)->prefix('policies')->group(function () {
    Route::get('', 'index')->name('admin.policies.index');
    Route::post('{id}/record-payment', 'recordPayment')->name('admin.policies.record_payment');
    Route::get('{id}/whatsapp-reminder', 'getWhatsAppReminder')->name('admin.policies.whatsapp_reminder');
    Route::post('{id}/renew', 'renew')->name('admin.policies.renew');
    Route::post('scan-grace-periods', 'scanGracePeriods')->name('admin.policies.scan_grace_periods');
});

Route::controller(ExecutiveAnalyticsController::class)->prefix('insurance/analytics')->group(function () {
    Route::get('', 'index')->name('admin.insurance.analytics.index');
    Route::get('data', 'data')->name('admin.insurance.analytics.data');
    Route::get('export-csv', 'exportCsv')->name('admin.insurance.analytics.export_csv');
});
