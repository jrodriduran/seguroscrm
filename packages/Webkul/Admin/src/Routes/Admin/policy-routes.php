<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Insurance\BookOfBusinessController;

Route::controller(BookOfBusinessController::class)->prefix('policies')->group(function () {
    Route::get('', 'index')->name('admin.policies.index');
    Route::post('{id}/record-payment', 'recordPayment')->name('admin.policies.record_payment');
    Route::get('{id}/whatsapp-reminder', 'getWhatsAppReminder')->name('admin.policies.whatsapp_reminder');
    Route::post('{id}/renew', 'renew')->name('admin.policies.renew');
    Route::post('scan-grace-periods', 'scanGracePeriods')->name('admin.policies.scan_grace_periods');
});
