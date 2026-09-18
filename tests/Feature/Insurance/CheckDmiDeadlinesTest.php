<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

it('runs insurance:check-dmi-deadlines command and creates activities for impending DMI deadlines', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'General Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Elena Perez',
        'contact_numbers' => [['value' => '4075551122', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Elena Perez - Florida Blue',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    // DMI expiring in 4 days (critical urgency)
    $doc = LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'person_id' => $person->id,
        'doc_type' => 'income',
        'title' => 'Prueba de Salario / Paystubs',
        'deadline_date' => Carbon::today()->addDays(4),
        'status' => 'pending_upload',
    ]);

    // 1. Dry run should not create activities
    $initialCount = $lead->activities()->count();
    Artisan::call('insurance:check-dmi-deadlines', ['--dry-run' => true]);
    expect($lead->activities()->count())->toBe($initialCount);

    // 2. Real run should create an urgent activity attached to the lead
    Artisan::call('insurance:check-dmi-deadlines');
    $lead->refresh();

    $newActivities = $lead->activities()
        ->where('activities.comment', 'LIKE', "%[DMI_DOC_ID:{$doc->id}]%")
        ->get();

    expect($newActivities)->not->toBeEmpty();
    $activity = $newActivities->first();
    expect($activity->title)->toContain('DMI CRÍTICO');
    expect($activity->priority)->toBe('urgent');
    expect($activity->comment)->toContain('Elena Perez');
    expect($activity->comment)->toContain('Prueba de Salario / Paystubs');

    // 3. Second run on same day should skip and NOT duplicate the activity
    $currentCount = $lead->activities()->count();
    Artisan::call('insurance:check-dmi-deadlines');
    expect($lead->activities()->count())->toBe($currentCount);
});
