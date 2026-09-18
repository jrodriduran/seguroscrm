<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Lead\ActivityController;
use Webkul\Admin\Http\Controllers\Lead\ConsentController;
use Webkul\Admin\Http\Controllers\Lead\DmiDocumentController;
use Webkul\Admin\Http\Controllers\Lead\EmailController;
use Webkul\Admin\Http\Controllers\Lead\EnrollmentPeriodController;
use Webkul\Admin\Http\Controllers\Lead\HouseholdMemberController;
use Webkul\Admin\Http\Controllers\Lead\LeadController;
use Webkul\Admin\Http\Controllers\Lead\QuoteController;
use Webkul\Admin\Http\Controllers\Lead\TagController;
use Webkul\Admin\Http\Controllers\Lead\TeamRadarController;
use Webkul\Admin\Http\Controllers\Medicare\MedicareSoaController;

Route::controller(LeadController::class)->prefix('leads')->group(function () {
    Route::get('', 'index')->name('admin.leads.index');

    Route::get('create', 'create')->name('admin.leads.create');

    Route::post('create', 'store')->name('admin.leads.store');

    Route::post('create-by-ai', 'createByAI')->name('admin.leads.create_by_ai');

    Route::get('view/{id}', 'view')->name('admin.leads.view');

    Route::get('edit/{id}', 'edit')->name('admin.leads.edit');

    Route::put('edit/{id}', 'update')->name('admin.leads.update');

    Route::put('attributes/edit/{id}', 'updateAttributes')->name('admin.leads.attributes.update');

    Route::put('stage/edit/{id}', 'updateStage')->name('admin.leads.stage.update');

    Route::get('search', 'search')->name('admin.leads.search');

    Route::delete('{id}', 'destroy')->name('admin.leads.delete');

    Route::post('mass-update', 'massUpdate')->name('admin.leads.mass_update');

    Route::post('mass-destroy', 'massDestroy')->name('admin.leads.mass_delete');

    Route::get('get/{pipeline_id?}', 'get')->name('admin.leads.get');

    Route::delete('product/{lead_id}', 'removeProduct')->name('admin.leads.product.remove');

    Route::put('product/{lead_id}', 'addProduct')->name('admin.leads.product.add');

    Route::get('kanban/look-up', [LeadController::class, 'kanbanLookup'])->name('admin.leads.kanban.look_up');

    Route::controller(ActivityController::class)->prefix('{id}/activities')->group(function () {
        Route::get('', 'index')->name('admin.leads.activities.index');
    });

    Route::controller(TagController::class)->prefix('{id}/tags')->group(function () {
        Route::post('', 'attach')->name('admin.leads.tags.attach');

        Route::delete('', 'detach')->name('admin.leads.tags.detach');
    });

    Route::controller(EmailController::class)->prefix('{id}/emails')->group(function () {
        Route::post('', 'store')->name('admin.leads.emails.store');

        Route::delete('', 'detach')->name('admin.leads.emails.detach');
    });

    Route::controller(QuoteController::class)->prefix('quotes')->group(function () {
        Route::post('{quote_id}/mail', 'mail')->name('admin.leads.quotes.mail');

        Route::delete('{quote_id?}', 'delete')->name('admin.leads.quotes.delete');
    });

    Route::controller(HouseholdMemberController::class)->prefix('{lead_id}/household-members')->group(function () {
        Route::get('', 'index')->name('admin.leads.household.index');
        Route::post('', 'store')->name('admin.leads.household.store');
        Route::put('{id}', 'update')->name('admin.leads.household.update');
        Route::delete('{id}', 'destroy')->name('admin.leads.household.delete');
    });

    Route::controller(ConsentController::class)->prefix('{lead_id}/consent')->group(function () {
        Route::get('', 'get')->name('admin.leads.consent.get');
        Route::get('whatsapp', 'getWhatsAppLink')->name('admin.leads.consent.whatsapp');
        Route::post('regenerate', 'regenerate')->name('admin.leads.consent.regenerate');
        Route::post('revoke', 'revoke')->name('admin.leads.consent.revoke');
        Route::get('certificate', 'printCertificate')->name('admin.leads.consent.certificate');
        Route::get('certificate/pdf', 'downloadCertificatePdf')->name('admin.leads.consent.certificate.pdf');
    });

    Route::controller(DmiDocumentController::class)->prefix('{lead_id}/dmi-documents')->group(function () {
        Route::get('', 'index')->name('admin.leads.dmi.index');
        Route::post('', 'store')->name('admin.leads.dmi.store');
        Route::put('{id}', 'update')->name('admin.leads.dmi.update');
        Route::delete('{id}', 'destroy')->name('admin.leads.dmi.delete');
        Route::get('{id}/whatsapp', 'getWhatsAppReminder')->name('admin.leads.dmi.whatsapp');
    });

    Route::controller(MedicareSoaController::class)->prefix('{lead_id}/medicare-soa')->group(function () {
        Route::get('', 'get')->name('admin.leads.soa.get');
        Route::get('whatsapp', 'getWhatsAppLink')->name('admin.leads.soa.whatsapp');
        Route::post('regenerate', 'regenerate')->name('admin.leads.soa.regenerate');
        Route::post('exception', 'applyException')->name('admin.leads.soa.exception');
        Route::get('certificate', 'printCertificate')->name('admin.leads.soa.certificate');
        Route::get('certificate/pdf', 'downloadCertificatePdf')->name('admin.leads.soa.certificate.pdf');
    });

    Route::controller(EnrollmentPeriodController::class)->prefix('{lead_id}/enrollment-period')->group(function () {
        Route::get('', 'get')->name('admin.leads.enrollment.get');
        Route::post('', 'save')->name('admin.leads.enrollment.save');
        Route::post('toggle-document', 'toggleDocument')->name('admin.leads.enrollment.toggle_document');
    });
});

// ─── Team Radar: Master Agent Control Tower ─────────────────────────────────
Route::controller(TeamRadarController::class)->prefix('leads')->group(function () {
    Route::get('team-radar', 'index')->name('admin.leads.team_radar.index');

    Route::get('team-radar/data-grid', 'dataGrid')->name('admin.leads.team_radar.data_grid');

    Route::get('my-pending', 'myPending')->name('admin.leads.my_pending');

    Route::post('bulk-reassign', 'bulkReassign')->name('admin.leads.bulk_reassign');

    Route::post('assign-manual', 'assignManual')->name('admin.leads.assign_manual');

    Route::post('assignment-rules', 'saveAssignmentRule')->name('admin.leads.assignment_rules.save');

    Route::post('sla-rules', 'saveSlaRule')->name('admin.leads.sla_rules.save');

    Route::post('{lead_id}/urgent-flag', 'urgentFlag')->name('admin.leads.urgent_flag');

    Route::post('{lead_id}/escalate', 'escalateLead')->name('admin.leads.escalate');

    Route::post('{lead_id}/resolve-escalation', 'resolveEscalation')->name('admin.leads.resolve_escalation');
});
