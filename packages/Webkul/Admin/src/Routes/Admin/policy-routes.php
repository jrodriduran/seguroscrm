<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Insurance\BookOfBusinessController;
use Webkul\Admin\Http\Controllers\Insurance\ExecutiveAnalyticsController;
use Webkul\Admin\Http\Controllers\Insurance\PolicyRenewalController;
use Webkul\Admin\Http\Controllers\Insurance\ServiceCaseController;

Route::controller(BookOfBusinessController::class)->prefix('policies')->group(function () {
    Route::get('', 'index')->name('admin.policies.index');
    Route::post('{id}/record-payment', 'recordPayment')->name('admin.policies.record_payment');
    Route::post('{id}/binder-payment', 'recordBinderPayment')->name('admin.policies.record_binder_payment');
    Route::get('{id}/coverage-history', 'coverageHistory')->name('admin.policies.coverage_history');
    Route::get('{id}/whatsapp-reminder', 'getWhatsAppReminder')->name('admin.policies.whatsapp_reminder');
    Route::post('{id}/renew', 'renew')->name('admin.policies.renew');
    Route::post('scan-grace-periods', 'scanGracePeriods')->name('admin.policies.scan_grace_periods');
});

Route::controller(PolicyRenewalController::class)->prefix('policies')->group(function () {
    Route::get('renewals/hub', 'hub')->name('admin.policies.renewals.hub');
    Route::get('{id}/renewal-comparison', 'comparison')->name('admin.policies.renewal_comparison');
    Route::post('{id}/process-renewal', 'process')->name('admin.policies.process_renewal');
});

Route::controller(ServiceCaseController::class)->prefix('policies/{policy_id}/service-cases')->group(function () {
    Route::get('', 'index')->name('admin.policies.service_cases.index');
    Route::post('', 'store')->name('admin.policies.service_cases.store');
});

Route::controller(ServiceCaseController::class)->prefix('service-cases')->group(function () {
    Route::get('', 'index')->name('admin.service_cases.index');
    Route::post('', 'store')->name('admin.service_cases.store');
    Route::get('{id}', 'show')->name('admin.service_cases.show');
    Route::put('{id}', 'update')->name('admin.service_cases.update');
    Route::post('{id}/comments', 'addComment')->name('admin.service_cases.add_comment');
    Route::get('{id}/download', 'downloadAttachment')->name('admin.service_cases.download');
});

Route::controller(ExecutiveAnalyticsController::class)->prefix('insurance/analytics')->group(function () {
    Route::get('', 'index')->name('admin.insurance.analytics.index');
    Route::get('data', 'data')->name('admin.insurance.analytics.data');
    Route::get('export-csv', 'exportCsv')->name('admin.insurance.analytics.export_csv');
});
