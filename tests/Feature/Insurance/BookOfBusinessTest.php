<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\PolicyRetentionService;
use Webkul\Quote\Models\Quote;

uses(DatabaseTransactions::class);

function createTestPolicy(string $status = 'active', ?string $paidToDate = null): InsurancePolicy
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Policy Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Won',
        'code' => 'won',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Elena Rostova',
        'emails' => [['value' => 'elena.rostova@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055556677', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Elena Rostova - Florida Blue Policy',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    return InsurancePolicy::create([
        'policy_number' => 'POL-FLB-'.rand(100000, 999999),
        'lead_id' => $lead->id,
        'person_id' => $person->id,
        'user_id' => 1,
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'BlueOptions Silver 1410',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'gross_premium' => 540.00,
        'aptc_subsidy' => 500.00,
        'net_premium' => 40.00,
        'effective_date' => Carbon::now()->subMonths(3)->startOfMonth(),
        'paid_to_date' => $paidToDate ?: Carbon::now()->addMonth()->endOfMonth(),
        'status' => $status,
        'members_count' => 1,
    ]);
}

it('returns Book of Business list and calculates accurate retention metrics', function () {
    $admin = getDefaultAdmin();

    $activePolicy = createTestPolicy('active');
    $gracePolicy = createTestPolicy('grace_period_1', Carbon::now()->subDays(15)->toDateString());

    $response = test()->actingAs($admin)
        ->getJson(route('admin.policies.index'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'metrics' => [
                'total_policies',
                'in_force_count',
                'persistency_rate',
                'net_monthly_volume',
                'grace_count',
            ],
            'policies',
        ]);
});

it('evaluates ACA 90-day grace periods and transitions policies according to overdue days', function () {
    // 1. Policy 15 days overdue -> Should enter grace_period_1
    $policy1 = createTestPolicy('active', Carbon::today()->subDays(15)->toDateString());

    // 2. Policy 45 days overdue -> Should enter grace_period_2_3 (critical grace)
    $policy2 = createTestPolicy('active', Carbon::today()->subDays(45)->toDateString());

    // 3. Policy 95 days overdue -> Should be cancelled/lapsed
    $policy3 = createTestPolicy('active', Carbon::today()->subDays(95)->toDateString());

    $service = app(PolicyRetentionService::class);
    $stats = $service->evaluateGracePeriods();

    expect($stats['total_evaluated'])->toBeGreaterThanOrEqual(3);

    $policy1->refresh();
    expect($policy1->status)->toBe('grace_period_1');
    expect($policy1->grace_period_days)->toBe(15);

    $policy2->refresh();
    expect($policy2->status)->toBe('grace_period_2_3');
    expect($policy2->grace_period_days)->toBe(45);

    $policy3->refresh();
    expect($policy3->status)->toBe('cancelled');
});

it('records a premium payment and restores policy from grace period to active', function () {
    $admin = getDefaultAdmin();
    $policy = createTestPolicy('grace_period_2_3', Carbon::today()->subDays(40)->toDateString());
    $policy->grace_period_days = 40;
    $policy->save();

    $newPaidTo = Carbon::today()->addMonth()->endOfMonth()->toDateString();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.policies.record_payment', $policy->id), [
            'paid_to_date' => $newPaidTo,
            'notes' => 'Pago de mensualidad verificado en portal Florida Blue.',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $policy->refresh();
    expect($policy->status)->toBe('active');
    expect($policy->grace_period_days)->toBe(0);
    expect($policy->paid_to_date->toDateString())->toBe($newPaidTo);
});

it('automatically creates an InsurancePolicy record when a health quote is converted to policy', function () {
    $admin = getDefaultAdmin();

    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Health', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);
    $person = Person::create(['name' => 'Marcos Vega']);
    $lead = Lead::create([
        'title' => 'Marcos Vega - Ambetter',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin?->id ?: 1,
    ]);

    $quote = Quote::create([
        'subject' => 'Ambetter Clear Silver',
        'carrier_name' => 'Ambetter',
        'plan_name' => 'Clear Silver 2026',
        'metal_tier' => 'silver',
        'gross_premium' => 480.00,
        'aptc_subsidy' => 450.00,
        'net_premium' => 30.00,
        'sub_total' => 480.00,
        'grand_total' => 30.00,
        'user_id' => $admin?->id ?: 1,
        'person_id' => $person->id,
        'quote_status' => 'draft',
    ]);

    $lead->quotes()->attach($quote->id);

    test()->actingAs($admin)
        ->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertOk();

    $policy = InsurancePolicy::where('quote_id', $quote->id)->first();
    expect($policy)->not->toBeNull();
    expect($policy->carrier_name)->toBe('Ambetter');
    expect($policy->net_premium)->toBe(30.00);
    expect($policy->status)->toBe('active');
});
