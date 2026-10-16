<?php

use Illuminate\Support\Facades\Route;
use Webkul\Insurance\Http\Controllers\HealthSherpaController;
use Webkul\Insurance\Http\Controllers\HouseholdTransitionController;

Route::controller(HouseholdTransitionController::class)->prefix('leads/{lead_id}/family-transitions')->group(function () {
    Route::get('', 'index')->name('admin.leads.family_transitions.index');
    Route::post('', 'store')->name('admin.leads.family_transitions.store');
    Route::put('{id}', 'resolve')->name('admin.leads.family_transitions.resolve');
    Route::post('{id}/individual', 'individual')->name('admin.leads.family_transitions.individual');
});

Route::controller(HealthSherpaController::class)->group(function () {
    Route::get('insurance/healthsherpa/readiness', 'readiness')->name('admin.insurance.healthsherpa.readiness');
    Route::get('insurance/healthsherpa/counties', 'counties')->name('admin.insurance.healthsherpa.counties');
    Route::post('leads/{lead_id}/healthsherpa/quotes', 'quotes')->name('admin.leads.healthsherpa.quotes');
});
