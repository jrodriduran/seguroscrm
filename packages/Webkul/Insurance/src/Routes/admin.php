<?php

use Illuminate\Support\Facades\Route;
use Webkul\Insurance\Http\Controllers\HealthSherpaController;

Route::controller(HealthSherpaController::class)->group(function () {
    Route::get('insurance/healthsherpa/readiness', 'readiness')->name('admin.insurance.healthsherpa.readiness');
    Route::get('insurance/healthsherpa/counties', 'counties')->name('admin.insurance.healthsherpa.counties');
    Route::post('leads/{lead_id}/healthsherpa/quotes', 'quotes')->name('admin.leads.healthsherpa.quotes');
});
