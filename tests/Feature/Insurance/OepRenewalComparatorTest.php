<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\PolicyCoverageStatusHistory;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestRenewalBasePolicy(array $attributes = []): InsurancePolicy
{
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'OEP Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => $attributes['client_name'] ?? 'Carla Gomez',
        'emails' => [['value' => 'carla.gomez@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865559988', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Carla Gomez - Florida Blue Renewal',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    return InsurancePolicy::create(array_merge([
        'policy_number' => 'POL-OEP-'.rand(100000, 999999),
        'portal_token' => Str::random(40),
        'member_id' => 'MBR-'.rand(10000000, 99999999),
        'group_number' => 'GRP-FLB-2025',
        'lead_id' => $lead->id,
        'person_id' => $person->id,
        'user_id' => 1,
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver 1410',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'market_type' => 'aca_individual',
        'plan_year' => 2025,
        'gross_premium' => 600.00,
        'aptc_subsidy' => 550.00,
        'net_premium' => 50.00,
        'deductible' => 1500.00,
        'max_out_of_pocket' => 7500.00,
        'effective_date' => '2025-01-01',
        'renewal_date' => '2025-12-31',
        'status' => 'active',
        'members_count' => 1,
    ], $attributes));
}

it('returns OEP renewal cohort with retention KPIs and policy list', function () {
    $user = User::first() ?: User::factory()->create();

    $policy1 = createTestRenewalBasePolicy(['plan_year' => 2025]);
    $policy2 = createTestRenewalBasePolicy(['plan_year' => 2025, 'carrier_name' => 'Ambetter']);

    $response = test()->actingAs($user)->getJson('/admin/policies/renewals/hub?plan_year=2026');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'plan_year',
                'prior_year',
                'kpis' => [
                    'total_cohort',
                    'renewed_total',
                    'renewed_same_carrier',
                    'renewed_cross_carrier',
                    'pending_review',
                    'retention_rate',
                    'retained_gross_volume',
                    'retained_net_volume',
                ],
                'policies',
            ],
        ]);

    $data = $response->json('data');
    expect($data['plan_year'])->toBe(2026);
    expect($data['prior_year'])->toBe(2025);
    expect($data['kpis']['total_cohort'])->toBeGreaterThanOrEqual(2);
});

it('processes year-over-year renewal with same carrier and plan switch', function () {
    $user = User::first() ?: User::factory()->create();
    $priorPolicy = createTestRenewalBasePolicy([
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver 1410',
        'gross_premium' => 600.00,
        'aptc_subsidy' => 550.00,
        'net_premium' => 50.00,
    ]);

    $renewalPayload = [
        'plan_year' => 2026,
        'carrier_name' => 'Florida Blue', // Same carrier
        'plan_name' => 'BlueCare Bronze 1500', // Plan switched
        'metal_tier' => 'bronze',
        'network_type' => 'HMO',
        'gross_premium' => 520.00,
        'aptc_subsidy' => 520.00,
        'net_premium' => 0.00, // $0 net premium qualifies for zero dollar effectuation
        'deductible' => 3000.00,
        'max_out_of_pocket' => 8500.00,
        'notes' => 'Cliente optó por plan bronce con prima neta cero.',
    ];

    $response = test()->actingAs($user)->postJson("/admin/policies/{$priorPolicy->id}/process-renewal", $renewalPayload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $priorPolicy->refresh();
    expect($priorPolicy->status)->toBe('renewed');

    $newPolicy = InsurancePolicy::where('prior_policy_id', $priorPolicy->id)->first();
    expect($newPolicy)->not->toBeNull();
    expect($newPolicy->plan_year)->toBe(2026);
    expect($newPolicy->carrier_name)->toBe('Florida Blue');
    expect($newPolicy->plan_name)->toBe('BlueCare Bronze 1500');
    expect($newPolicy->renewal_type)->toBe('same_carrier_switch');
    expect($newPolicy->net_premium)->toBe(0.0);
    // $0 premium auto-effectuated
    expect($newPolicy->status)->toBe('active');
    expect($newPolicy->binder_payment_status)->toBe('waived_zero_premium');

    // Verify audit status histories
    $statusHistories = PolicyCoverageStatusHistory::where('policy_id', $newPolicy->id)->get();
    expect($statusHistories->count())->toBeGreaterThanOrEqual(1);
});

it('processes cross-carrier renewal and flags binder pending for positive premium', function () {
    $user = User::first() ?: User::factory()->create();
    $priorPolicy = createTestRenewalBasePolicy([
        'carrier_name' => 'Florida Blue',
        'gross_premium' => 600.00,
        'aptc_subsidy' => 550.00,
        'net_premium' => 50.00,
    ]);

    $renewalPayload = [
        'plan_year' => 2026,
        'carrier_name' => 'Ambetter Health', // Cross-carrier
        'plan_name' => 'Ambetter Balanced Care 11',
        'metal_tier' => 'silver',
        'network_type' => 'HMO',
        'gross_premium' => 650.00,
        'aptc_subsidy' => 580.00,
        'net_premium' => 70.00, // Positive premium requires binder
        'deductible' => 1200.00,
        'max_out_of_pocket' => 6800.00,
    ];

    $response = test()->actingAs($user)->postJson("/admin/policies/{$priorPolicy->id}/process-renewal", $renewalPayload);

    $response->assertOk();

    $newPolicy = InsurancePolicy::where('prior_policy_id', $priorPolicy->id)->first();
    expect($newPolicy)->not->toBeNull();
    expect($newPolicy->renewal_type)->toBe('cross_carrier_switch');
    expect($newPolicy->status)->toBe('binder_pending');
    expect($newPolicy->binder_payment_status)->toBe('pending');
});

it('computes year-over-year comparison and detects variance and warnings', function () {
    $user = User::first() ?: User::factory()->create();

    $priorPolicy = createTestRenewalBasePolicy([
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver 1410',
        'gross_premium' => 550.00,
        'aptc_subsidy' => 550.00,
        'net_premium' => 0.00,
        'deductible' => 1000.00,
    ]);

    $newPolicy = InsurancePolicy::create([
        'policy_number' => 'POL-RENEWED-2026',
        'lead_id' => $priorPolicy->lead_id,
        'carrier_name' => 'Oscar Health', // Carrier changed
        'plan_name' => 'Oscar Classic Silver',
        'metal_tier' => 'silver',
        'plan_year' => 2026,
        'gross_premium' => 620.00,
        'aptc_subsidy' => 480.00, // Subsidy dropped
        'net_premium' => 140.00, // Net premium increased
        'deductible' => 1500.00,
        'max_out_of_pocket' => 8000.00,
        'status' => 'binder_pending',
        'prior_policy_id' => $priorPolicy->id,
        'renewal_type' => 'cross_carrier_switch',
    ]);

    $response = test()->actingAs($user)->getJson("/admin/policies/{$newPolicy->id}/renewal-comparison");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'comparison' => [
                'prior_policy',
                'current_policy',
                'variance' => [
                    'gross_premium_diff',
                    'aptc_subsidy_diff',
                    'net_premium_diff',
                    'deductible_diff',
                    'moop_diff',
                    'is_carrier_changed',
                    'is_plan_changed',
                    'is_net_increase',
                    'subsidy_loss_warning',
                ],
            ],
        ]);

    $variance = $response->json('comparison.variance');
    expect($variance['is_carrier_changed'])->toBeTrue();
    expect($variance['is_net_increase'])->toBeTrue();
    expect($variance['net_premium_diff'])->toBe(140.0);
    expect($variance['subsidy_diff'])->toBe(-70.0);
    expect($variance['subsidy_loss_warning'])->toBeTrue();
});
