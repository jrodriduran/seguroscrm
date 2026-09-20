<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\CarrierStatement;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestCommissionForReconcile(string $policyNo, string $carrier, float $gross, string $clientName): InsuranceCommission
{
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);
    $person = Person::create(['name' => $clientName]);

    $lead = Lead::create([
        'title' => "{$clientName} - {$carrier}",
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    return InsuranceCommission::create([
        'lead_id' => $lead->id,
        'user_id' => 1,
        'policy_number' => $policyNo,
        'carrier_name' => $carrier,
        'plan_name' => 'Silver Standard',
        'metal_tier' => 'Silver',
        'members_count' => 1,
        'commission_type' => 'pmpm',
        'rate_per_member' => $gross,
        'gross_monthly' => $gross,
        'agent_split_percentage' => 70.0,
        'agent_monthly' => round($gross * 0.7, 2),
        'agency_monthly' => round($gross * 0.3, 2),
        'status' => 'active',
        'effective_date' => now()->startOfMonth(),
    ]);
}

it('allows admin to access carrier reconciliation dashboard', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.commissions.reconciliation.index', ['locale' => 'es']))
        ->assertOk()
        ->assertSee('Reconciliador de Statements de Aseguradoras');
});

it('processes carrier statement CSV and identifies exact match, variance, orphan, and missed commissions', function () {
    $admin = getDefaultAdmin();

    // 1. Setup existing active policies in CRM
    // Policy 1: Exact match expected ($28 paid vs $28 expected)
    $comm1 = createTestCommissionForReconcile('FLB-1001', 'Florida Blue', 28.0, 'Juan Perez');

    // Policy 2: Variance expected ($20 paid vs $28 expected)
    $comm2 = createTestCommissionForReconcile('FLB-1002', 'Florida Blue', 28.0, 'Maria Gomez');

    // Policy 3: Missed Commission! Active in CRM, but carrier statement leaves it out!
    $comm3 = createTestCommissionForReconcile('FLB-1003', 'Florida Blue', 56.0, 'Carlos Diaz');

    // 2. Prepare sample CSV from Florida Blue
    // Contains:
    // - FLB-1001: 28.00 (Exact)
    // - FLB-1002: 20.00 (Variance: underpaid by $8)
    // - FLB-9999: 28.00 (Orphan: not in CRM)
    // - FLB-8888: -28.00 (Chargeback: cancelled)
    // Notice FLB-1003 is omitted from the CSV!
    $csvContent = "Policy_Number,Insured_Name,Commission_Paid\n"
        ."FLB-1001,Juan Perez,28.00\n"
        ."FLB-1002,Maria Gomez,20.00\n"
        ."FLB-9999,Orphan Client,28.00\n"
        ."FLB-8888,Cancelled Client,-28.00\n";

    $response = test()->actingAs($admin)->postJson(route('admin.commissions.reconciliation.upload'), [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-02',
        'csv_content' => $csvContent,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $statement = CarrierStatement::with('items')->latest()->first();
    expect($statement)->not->toBeNull();
    expect($statement->carrier_name)->toBe('Florida Blue');
    expect($statement->period_month)->toBe('2026-02');

    // Total records = 4 CSV rows + 1 Missed commission = 5 items
    expect($statement->total_records)->toBe(5);
    expect($statement->matched_records)->toBe(1); // FLB-1001
    expect($statement->missed_records)->toBe(1);  // FLB-1003
    expect((float) $statement->total_missed_amount)->toBe(56.0);

    // Verify items
    $exactItem = $statement->items->firstWhere('policy_number', 'FLB-1001');
    expect($exactItem)->not->toBeNull();
    expect($exactItem->match_status)->toBe('matched_exact');
    expect((float) $exactItem->carrier_amount)->toBe(28.0);
    expect((float) $exactItem->difference)->toBe(0.0);

    $varianceItem = $statement->items->firstWhere('policy_number', 'FLB-1002');
    expect($varianceItem)->not->toBeNull();
    expect($varianceItem->match_status)->toBe('matched_variance');
    expect((float) $varianceItem->difference)->toBe(-8.0);

    $missedItem = $statement->items->firstWhere('policy_number', 'FLB-1003');
    expect($missedItem)->not->toBeNull();
    expect($missedItem->match_status)->toBe('missed_commission');
    expect((float) $missedItem->expected_amount)->toBe(56.0);
    expect((float) $missedItem->carrier_amount)->toBe(0.0);

    $chargebackItem = $statement->items->firstWhere('policy_number', 'FLB-8888');
    expect($chargebackItem)->not->toBeNull();
    expect($chargebackItem->match_status)->toBe('chargeback');

    $orphanItem = $statement->items->firstWhere('policy_number', 'FLB-9999');
    expect($orphanItem)->not->toBeNull();
    expect($orphanItem->match_status)->toBe('unmatched_orphan');
});
