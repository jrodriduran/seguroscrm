<?php

use Illuminate\Support\Facades\Route;
use Webkul\Communications\Http\Controllers\CallOutcomeController;
use Webkul\Communications\Http\Controllers\ChatwootSettingsController;
use Webkul\Communications\Http\Controllers\ClientCommunicationController;
use Webkul\Communications\Http\Controllers\EnrollmentController;
use Webkul\Communications\Http\Controllers\PersonContactController;
use Webkul\Communications\Http\Controllers\SequenceController;
use Webkul\Communications\Http\Controllers\StageRuleController;
use Webkul\Communications\Http\Controllers\TemplateController;

Route::get('contacts/persons/{id}/communications', [ClientCommunicationController::class, 'person'])
    ->name('admin.communications.persons.show');

Route::post('contacts/persons/{id}/communications/reply', [ClientCommunicationController::class, 'reply'])
    ->name('admin.communications.persons.reply');

Route::post('contacts/persons/{id}/communications/preferences', [PersonContactController::class, 'preferences'])
    ->name('admin.communications.persons.preferences');

Route::post('contacts/persons/{id}/communications/consent', [PersonContactController::class, 'consent'])
    ->name('admin.communications.persons.consent');

Route::get('communications/zip/{zip}', [PersonContactController::class, 'zip'])
    ->where('zip', '[0-9]{5}')
    ->name('admin.communications.zip');

Route::controller(CallOutcomeController::class)->prefix('settings/communications/call-outcomes')->group(function () {
    Route::get('', 'index')->name('admin.settings.communications.outcomes.index');

    Route::post('', 'store')->name('admin.settings.communications.outcomes.store');

    Route::put('{id}', 'update')->name('admin.settings.communications.outcomes.update');

    Route::post('{id}/toggle', 'toggle')->name('admin.settings.communications.outcomes.toggle');

    Route::post('{id}/move', 'move')->name('admin.settings.communications.outcomes.move');

    Route::delete('{id}', 'destroy')->name('admin.settings.communications.outcomes.delete');
});

Route::controller(ChatwootSettingsController::class)->prefix('settings/communications/chatwoot')->group(function () {
    Route::get('', 'index')->name('admin.settings.communications.chatwoot.index');

    Route::post('', 'update')->name('admin.settings.communications.chatwoot.update');

    Route::post('disconnect', 'disconnect')->name('admin.settings.communications.chatwoot.disconnect');
});

Route::controller(TemplateController::class)->prefix('communications/templates')->group(function () {
    Route::get('', 'index')->name('admin.communications.templates.index');

    Route::get('create', 'create')->name('admin.communications.templates.create');

    Route::post('', 'store')->name('admin.communications.templates.store');

    Route::post('settings', 'settings')->name('admin.communications.templates.settings');

    Route::get('{id}/edit', 'edit')->name('admin.communications.templates.edit');

    Route::put('{id}', 'update')->name('admin.communications.templates.update');

    Route::delete('{id}', 'destroy')->name('admin.communications.templates.delete');
});

Route::controller(SequenceController::class)->prefix('communications/sequences')->group(function () {
    Route::get('', 'index')->name('admin.communications.sequences.index');

    Route::get('create', 'create')->name('admin.communications.sequences.create');

    Route::post('', 'store')->name('admin.communications.sequences.store');

    Route::get('{id}/edit', 'edit')->name('admin.communications.sequences.edit');

    Route::put('{id}', 'update')->name('admin.communications.sequences.update');

    Route::post('{id}/toggle', 'toggle')->name('admin.communications.sequences.toggle');

    Route::delete('{id}', 'destroy')->name('admin.communications.sequences.delete');
});

Route::post('contacts/persons/{id}/sequences', [EnrollmentController::class, 'store'])->name('admin.communications.enrollments.store');

Route::controller(EnrollmentController::class)->prefix('communications/enrollments/{id}')->group(function () {
    Route::post('pause', 'pause')->name('admin.communications.enrollments.pause');

    Route::post('resume', 'resume')->name('admin.communications.enrollments.resume');

    Route::post('stop', 'stop')->name('admin.communications.enrollments.stop');
});

Route::controller(StageRuleController::class)->prefix('settings/communications/stages')->group(function () {
    Route::get('', 'index')->name('admin.settings.communications.stages.index');

    Route::post('', 'store')->name('admin.settings.communications.stages.store');

    Route::delete('{id}', 'destroy')->name('admin.settings.communications.stages.delete');
});
