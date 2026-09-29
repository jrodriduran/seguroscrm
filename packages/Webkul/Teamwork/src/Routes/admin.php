<?php

use Illuminate\Support\Facades\Route;
use Webkul\Teamwork\Http\Controllers\FollowUpCenterController;
use Webkul\Teamwork\Http\Controllers\AutomationController;
use Webkul\Teamwork\Http\Controllers\FollowerController;
use Webkul\Teamwork\Http\Controllers\FollowUpController;
use Webkul\Teamwork\Http\Controllers\NoteController;
use Webkul\Teamwork\Http\Controllers\NotificationController;
use Webkul\Teamwork\Http\Controllers\OverdueRuleController;
use Webkul\Teamwork\Http\Controllers\PipelineHealthController;
use Webkul\Teamwork\Http\Controllers\RecordController;

/**
 * Everyone: their own work and the follow-ups they take part in.
 */
Route::get('follow-up', [FollowUpCenterController::class, 'index'])->name('admin.teamwork.center');

Route::get('dashboard/pipeline-health', [PipelineHealthController::class, 'index'])->name('admin.dashboard.pipeline_health');

Route::controller(FollowUpController::class)->prefix('follow-ups')->group(function () {
    Route::post('', 'store')->name('admin.teamwork.follow_ups.store');

    Route::post('handoff', 'handoff')->name('admin.teamwork.follow_ups.handoff');

    Route::get('{id}', 'show')->name('admin.teamwork.follow_ups.show');

    Route::post('{id}/comments', 'comment')->name('admin.teamwork.follow_ups.comment');

    Route::post('{id}/resolve', 'resolve')->name('admin.teamwork.follow_ups.resolve');

    Route::post('{id}/reopen', 'reopen')->name('admin.teamwork.follow_ups.reopen');
});

Route::get('follow-up/record/{type}/{id}', [RecordController::class, 'show'])->name('admin.teamwork.records.show');

Route::post('follow-up/record/{type}/{id}/follow', [FollowerController::class, 'toggle'])->name('admin.teamwork.records.follow');

Route::controller(NoteController::class)->prefix('team-notes')->group(function () {
    Route::post('', 'store')->name('admin.teamwork.notes.store');

    Route::get('{id}', 'show')->name('admin.teamwork.notes.show');

    Route::post('{id}/follow-up', 'convert')->name('admin.teamwork.notes.convert');
});

Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('', 'index')->name('admin.teamwork.notifications.index');

    Route::get('count', 'count')->name('admin.teamwork.notifications.count');

    Route::get('{id}/open', 'open')->name('admin.teamwork.notifications.open');

    Route::post('read-all', 'readAll')->name('admin.teamwork.notifications.read_all');
});

/**
 * Administration: automations (ACL protected).
 */
Route::controller(AutomationController::class)->prefix('settings/teamwork/automations')->group(function () {
    Route::get('', 'index')->name('admin.settings.teamwork.automations.index');

    Route::post('', 'store')->name('admin.settings.teamwork.automations.store');

    Route::post('{id}/toggle', 'toggle')->name('admin.settings.teamwork.automations.toggle');

    Route::delete('{id}', 'destroy')->name('admin.settings.teamwork.automations.delete');
});

/**
 * Administration: overdue rules (ACL protected).
 */
Route::controller(OverdueRuleController::class)->prefix('settings/teamwork/rules')->group(function () {
    Route::get('', 'index')->name('admin.settings.teamwork.rules.index');

    Route::post('', 'store')->name('admin.settings.teamwork.rules.store');

    Route::post('{id}/toggle', 'toggle')->name('admin.settings.teamwork.rules.toggle');

    Route::delete('{id}', 'destroy')->name('admin.settings.teamwork.rules.delete');
});
