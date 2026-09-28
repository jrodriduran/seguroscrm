<?php

use Webkul\Admin\Http\Controllers\Insurance\AgentLedgerController;
use Webkul\Admin\Http\Controllers\Insurance\CommissionController;

Route::group(['middleware' => ['web', 'admin_locale', 'user']], function () {
    Route::controller(CommissionController::class)->prefix('commissions')->group(function () {
        Route::get('', 'index')->name('admin.commissions.index');
        Route::post('', 'store')->name('admin.commissions.store');
        Route::put('{id}', 'update')->name('admin.commissions.update');
        Route::delete('{id}', 'destroy')->name('admin.commissions.destroy');
        Route::put('rates/{id}', 'updateRate')->name('admin.commissions.rates.update');

        // Statements Reconciliation
        Route::get('reconciliation', 'reconciliationIndex')->name('admin.commissions.reconciliation.index');
        Route::post('reconciliation/upload', 'uploadStatement')->name('admin.commissions.reconciliation.upload');
        Route::post('upload-statement', 'uploadStatement');
        Route::get('reconciliation/{id}', 'showStatement')->name('admin.commissions.reconciliation.show');
        Route::delete('reconciliation/{id}', 'deleteStatement')->name('admin.commissions.reconciliation.destroy');
    });

    Route::controller(CommissionController::class)->prefix('insurance/commissions')->group(function () {
        Route::post('upload-statement', 'uploadStatement')->name('admin.insurance.commissions.upload_statement');
    });

    Route::controller(AgentLedgerController::class)->prefix('insurance/ledger')->group(function () {
        Route::get('', 'index')->name('admin.insurance.ledger.index');
        Route::get('{userId}', 'agentHistory')->name('admin.insurance.ledger.agent_history');
        Route::post('{userId}/clawback', 'recordClawback')->name('admin.insurance.ledger.clawback');
        Route::post('{userId}/disburse', 'disburse')->name('admin.insurance.ledger.disburse');
        Route::post('{userId}/adjustment', 'adjustment')->name('admin.insurance.ledger.adjustment');
    });
});
