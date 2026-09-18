<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\LeadSepQualification;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\LeadAiScoringService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestLeadWithPerson(string $name, ?string $dob = null): Lead
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'AI Scoring Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => $name,
        'emails' => [['value' => strtolower(str_replace(' ', '.', $name)).'@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055551234', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => "$name - AI Evaluation",
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
    ]);
}

it('evaluates lead with urgent SEP expiring in less than 7 days as high priority', function () {
    $lead = createTestLeadWithPerson('Elena Delgado');

    // Create SEP qualification with event date 55 days ago (only 5 days remaining in 60-day window)
    LeadSepQualification::create([
        'lead_id' => $lead->id,
        'status' => 'qualified',
        'qle_type' => 'loss_of_coverage',
        'event_date' => Carbon::now()->subDays(55)->toDateString(),
        'window_deadline' => Carbon::now()->addDays(5)->toDateString(),
        'coverage_effective_date' => Carbon::now()->addMonth()->startOfMonth()->toDateString(),
    ]);

    $service = app(LeadAiScoringService::class);
    $result = $service->evaluateLead($lead);

    expect($result['total_score'])->toBeGreaterThanOrEqual(45);
    expect($result['breakdown']['sep_oep_urgency'])->toEqual(30);

    $actions = $result['next_best_actions'];
    expect($actions)->not->toBeEmpty();
    expect($actions[0]['priority'])->toEqual('urgent');
    expect($actions[0]['title'])->toContain('Ventana SEP por vencer en 5 días');
});

it('detects turning 65 Medicare initial enrollment period opportunity', function () {
    $lead = createTestLeadWithPerson('Roberto Morales');

    // Add household member turning 65 in 2 months (64 years and 10 months)
    $lead->householdMembers()->create([
        'name' => 'Roberto Morales',
        'relationship' => 'primary_subscriber',
        'date_of_birth' => Carbon::now()->subYears(65)->addMonths(2)->toDateString(),
        'is_applying_coverage' => true,
    ]);

    $service = app(LeadAiScoringService::class);
    $result = $service->evaluateLead($lead);

    expect($result['breakdown']['medicare_turning_65'])->toEqual(25);

    $hasMedicareAction = collect($result['next_best_actions'])->contains(fn ($a) => str_contains($a['title'], 'Oportunidad Medicare'));
    expect($hasMedicareAction)->toBeTrue();
});

it('boosts score and triggers urgent alert when critical DMI document deadline is pending', function () {
    $lead = createTestLeadWithPerson('Lucia Mendez');

    LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'title' => 'Comprobante de Ingresos 1040',
        'document_type' => 'income_verification',
        'status' => 'pending_upload',
        'deadline_date' => Carbon::now()->addDays(6)->toDateString(),
    ]);

    $service = app(LeadAiScoringService::class);
    $result = $service->evaluateLead($lead);

    expect($result['breakdown']['retention_dmi_urgency'])->toEqual(10);
    $hasDmiAlert = collect($result['next_best_actions'])->contains(fn ($a) => str_contains($a['title'], 'Documento DMI Crítico'));
    expect($hasDmiAlert)->toBeTrue();
});

it('returns AI evaluation data from JSON endpoint', function () {
    $lead = createTestLeadWithPerson('Mario Casas');
    $admin = User::first();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.leads.ai_insights', $lead->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'evaluation' => [
                'lead_id' => $lead->id,
            ],
        ]);

    expect($response->json('evaluation.total_score'))->toBeInt();
    expect($response->json('evaluation.tier.label'))->toBeString();
});
