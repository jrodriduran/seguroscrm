<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadCrossSellOpportunity;
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
        'first_name' => 'Elena',
        'last_name' => 'Gutierrez',
        'relationship' => 'spouse',
        'date_of_birth' => '1985-04-12',
        'gender' => 'female',
        'is_applicant' => 1,
        'tobacco_user' => 0,
    ]);

    $bridgeService = app(HealthSherpaBridgeService::class);
    $payload = $bridgeService->buildPrefillPayload($lead);

    expect($payload)->toBeArray();
    expect($payload['household_size'])->toBeGreaterThanOrEqual(2);
    expect($payload['redirect_url'])->toContain('healthsherpa.com');
    expect($payload['members'])->toHaveCount(2); // Applicant + Spouse
    expect($payload['members'][0]['is_primary'])->toBeTrue();
});

it('evaluates cross-sell opportunities for ancillary bundles', function () {
    $lead = createTestCompetitiveLead();

    $crossSellService = app(CrossSellOpportunityService::class);
    $opportunities = $crossSellService->evaluateLead($lead);

    expect($opportunities)->toBeCollection();
    expect($opportunities->count())->toBeGreaterThanOrEqual(3);

    $productTypes = $opportunities->pluck('product_type')->toArray();
    expect($productTypes)->toContain('dental');
    expect($productTypes)->toContain('vision');
    expect($productTypes)->toContain('hospital_indemnity');

    $dental = $opportunities->firstWhere('product_type', 'dental');
    expect($dental->est_annual_commission)->toBeGreaterThan(0);
    expect($dental->pitch_script)->not->toBeEmpty();
});

it('tracks producer licensing, AHIP certification and E&O insurance compliance', function () {
    $agent = User::first();

    // Create resident active license in Florida
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
        'ahip_certified' => true,
        'ahip_year' => 2026,
        'eo_carrier' => 'CalSurance Associates',
        'eo_policy_number' => 'EO-2026-99182',
        'eo_expiration_date' => now()->addMonths(8),
        'eo_coverage_amount' => 1000000.00,
    ]);

    $complianceService = app(AgentComplianceService::class);
    $audit = $complianceService->checkAgentCompliance($agent->id);

    expect($audit)->toBeArray();
    expect($audit['is_compliant'])->toBeTrue();
    expect($audit['resident_state'])->toBe('FL');
    expect($audit['ahip']['is_certified'])->toBeTrue();
    expect($audit['eo']['has_active_policy'])->toBeTrue();
});

it('synthesizes an instant 360 AI client narrative snapshot', function () {
    $lead = createTestCompetitiveLead();

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'first_name' => 'Mateo',
        'last_name' => 'Gutierrez',
        'relationship' => 'child',
        'date_of_birth' => '2015-08-20',
        'gender' => 'male',
        'is_applicant' => 1,
        'tobacco_user' => 0,
    ]);

    $snapshotService = app(ClientSnapshotService::class);
    $snapshot = $snapshotService->generateSnapshot($lead);

    expect($snapshot)->toBeArray();
    expect($snapshot)->toHaveKeys([
        'lead_id',
        'lead_title',
        'household',
        'compliance_summary',
        'cross_sell_bundle',
        'clinical_profile',
        'ai_actionable_brief',
    ]);
    expect($snapshot['household']['size'])->toBe(2);
    expect($snapshot['ai_actionable_brief'])->toContain('Roberto Carlos Gutierrez');
});
