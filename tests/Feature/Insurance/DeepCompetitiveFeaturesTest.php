<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\Lead\Services\AgentComplianceService;
use Webkul\Lead\Services\ClientSnapshotService;
use Webkul\Lead\Services\CrossSellOpportunityService;
use Webkul\Lead\Services\HealthSherpaBridgeService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestCompetitiveLead(): Lead
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Health Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Roberto Carlos Gutierrez',
        'emails' => [['value' => 'roberto.gutierrez@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055557766', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Roberto Gutierrez - ACA & Bundle Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'lead_value' => 450,
    ]);
}

it('generates a full HealthSherpa prefill payload and deep link URL', function () {
    $lead = createTestCompetitiveLead();

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Elena Gutierrez',
        'relationship' => 'spouse',
        'date_of_birth' => '1985-04-12',
        'gender' => 'female',
        'is_applying_coverage' => 1,
    ]);

    $bridgeService = app(HealthSherpaBridgeService::class);
    $payload = $bridgeService->generateBridgeData($lead);

    expect($payload)->toBeArray();
    expect($payload['household_size'])->toBeGreaterThanOrEqual(2);
    expect($payload['deep_link_url'])->toContain('healthsherpa.com');
    expect($payload['applicants'])->toHaveCount(2);
    expect($payload['applicants'][0]['role'])->toBe('primary');
});

it('evaluates cross-sell opportunities for ancillary bundles', function () {
    $lead = createTestCompetitiveLead();

    $crossSellService = app(CrossSellOpportunityService::class);
    $result = $crossSellService->evaluateAndSync($lead);

    expect($result)->toBeArray();
    expect($result['opportunities']->count())->toBeGreaterThanOrEqual(3);

    $productTypes = $result['opportunities']->pluck('product_type')->toArray();
    expect($productTypes)->toContain('dental_vision');
    expect($productTypes)->toContain('hospital_indemnity');
    expect($productTypes)->toContain('final_expense');

    $dental = $result['opportunities']->firstWhere('product_type', 'dental_vision');
    expect((float) $dental->estimated_agent_commission)->toBeGreaterThan(0);
    expect($dental->gap_reason)->not->toBeEmpty();
});

it('tracks producer licensing, AHIP certification and E&O insurance compliance', function () {
    $agent = User::first();

    UserAgentLicense::create([
        'user_id' => $agent->id,
        'state_code' => 'FL',
        'license_number' => 'W123456',
        'license_type' => 'resident',
        'status' => 'active',
        'lines_of_authority' => ['health', 'life', 'variable_annuity'],
        'npn' => '18999888',
        'issue_date' => now()->subYear(),
        'expiration_date' => now()->addMonths(11),
    ]);

    $complianceService = app(AgentComplianceService::class);
    $audit = $complianceService->getAgentCompliance($agent->id);

    expect($audit)->toBeArray();
    expect($audit['user_id'])->toBe($agent->id);
    expect($audit['licenses'])->toHaveCount(1);
    expect($audit['licenses']->first()->state_code)->toBe('FL');
});

it('synthesizes an instant 360 AI client narrative snapshot', function () {
    $lead = createTestCompetitiveLead();

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Mateo Gutierrez',
        'relationship' => 'child',
        'date_of_birth' => '2015-08-20',
        'gender' => 'male',
        'is_applying_coverage' => 1,
    ]);

    $snapshotService = app(ClientSnapshotService::class);
    $snapshot = $snapshotService->generateSnapshot($lead);

    expect($snapshot)->toBeArray();
    expect($snapshot)->toHaveKeys([
        'lead_id',
        'client_name',
        'household_size',
        'compliance_status',
        'ai_score',
        'executive_narrative',
    ]);
    expect($snapshot['household_size'])->toBe(2);
    expect($snapshot['executive_narrative'])->toContain('Roberto Carlos Gutierrez');
});
