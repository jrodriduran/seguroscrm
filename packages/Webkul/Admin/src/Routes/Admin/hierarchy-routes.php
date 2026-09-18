<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Insurance\AgencyHierarchyController;

Route::controller(AgencyHierarchyController::class)->prefix('hierarchy')->group(function () {
    Route::get('', 'index')->name('admin.hierarchy.index');
    Route::post('save', 'saveHierarchy')->name('admin.hierarchy.save');
    Route::get('ledger', 'overridesLedger')->name('admin.hierarchy.ledger');
    Route::get('export', 'exportReport')->name('admin.hierarchy.export');
});
