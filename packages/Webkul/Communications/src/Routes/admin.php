<?php

use Illuminate\Support\Facades\Route;
use Webkul\Communications\Http\Controllers\CallOutcomeController;
use Webkul\Communications\Http\Controllers\ClientCommunicationController;
use Webkul\Communications\Http\Controllers\PersonContactController;

Route::get('contacts/persons/{id}/communications', [ClientCommunicationController::class, 'person'])
    ->name('admin.communications.persons.show');

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
