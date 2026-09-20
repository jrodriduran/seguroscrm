<?php

use Webkul\Admin\Http\Controllers\Chatwoot\ChatwootWebhookController;
use Webkul\Admin\Http\Controllers\ConsentPortalController;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\Insurance\InsuredPortalController;
use Webkul\Admin\Http\Controllers\Medicare\MedicareSoaPortalController;
use Webkul\Admin\Http\Controllers\Quote\ProposalPortalController;

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

    Route::get('{token}', 'show')->name('consent.portal');
    Route::post('{token}', 'sign')->name('consent.sign');
});

/**
 * Medicare Scope of Appointment (SOA) Public Signing Portal Routes (CMS Compliance)
 */
Route::controller(MedicareSoaPortalController::class)->prefix('medicare-soa')->group(function () {
    Route::get('{token}', 'show')->name('medicare.soa.portal');
    Route::post('{token}', 'sign')->name('medicare.soa.sign');
    Route::get('{token}/signed', 'signed')->name('medicare.soa.signed');
});

/**
 * Health Insurance Proposal Comparison Public Portal Routes
 */
Route::controller(ProposalPortalController::class)->prefix('proposal')->group(function () {
    Route::get('{token}', 'show')->name('proposal.portal.show');
    Route::post('{token}/select', 'selectPlan')->name('proposal.portal.select');
    Route::get('{token}/thankyou', 'thankYou')->name('proposal.portal.thankyou');
});

/**
 * Insured Self-Service Portal & Digital ID Card Routes
 */
Route::controller(InsuredPortalController::class)->prefix('my-policy')->group(function () {
    Route::get('{token}', 'show')->name('insured.portal.show');
    Route::get('{token}/card-pdf', 'downloadCard')->name('insured.portal.download_card');
    Route::post('{token}/upload-doc', 'uploadDocument')->name('insured.portal.upload_doc');
});

/**
 * Chatwoot Webhook Integration Route (Omnichannel Sync)
 */
Route::post('api/chatwoot/webhook', [ChatwootWebhookController::class, 'handle'])->name('chatwoot.webhook');
