<?php

use Illuminate\Support\Facades\Route;
use Webkul\Communications\Http\Controllers\ClientCommunicationController;

Route::get('contacts/persons/{id}/communications', [ClientCommunicationController::class, 'person'])
    ->name('admin.communications.persons.show');
