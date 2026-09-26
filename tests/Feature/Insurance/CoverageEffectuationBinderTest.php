<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\PolicyCoverageStatusHistory;
use Webkul\Lead\Models\Stage;
use Webkul\Quote\Models\Quote;

uses(DatabaseTransactions::class);

function createCoverageTestLead(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Coverage Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Won',
        'code' => 'won',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Roberto Hernandez',
        'emails' => [['value' => 'roberto.h@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865553344', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Roberto Hernandez - Ambetter Silver',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('places policies with positive net premium into binder_pending coverage status upon quote conversion', function () {
    $lead = createCoverageTestLead();

    $quote = Quote::create([
        'subject' => 'Ambetter Balanced Care 11',
        'user_id' => 1,
        'person_id' => $lead->person_id,
        'carrier_name' => 'Ambetter',
        'plan_name' => 'Balanced Care 11',
        'metal_tier' => 'silver',
        'network_type' => 'HMO',
        'gross_premium' => 450.00,
        'aptc_subsidy' => 400.00,
        'net_premium' => 50.00, // Monthly premium > 0 requires binder payment
        'effective_date' => Carbon::now()->addMonth()->startOfMonth(),
        'status' => 'approved',
    ]);

    $policy = InsurancePolicy::createOrUpdateFromQuote($quote, $lead->id, 'POL-AMB-100200');

    expect($policy)->toBeInstanceOf(InsurancePolicy::class);
    expect($policy->status)->toBe('binder_pending');
    expect($policy->effectuation_status)->toBe('pending_binder');
    expect($policy->binder_payment_status)->toBe('pending');
    expect($policy->net_premium)->toEqual(50.00);

    // Verify history audit logged
    $history = PolicyCoverageStatusHistory::where('policy_id', $policy->id)->latest()->first();
    expect($history)->not->toBeNull();
    expect($history->to_status)->toBe('binder_pending');
    expect($history->reason)->toContain('positive net premium');
});

it('immediately activates $0 net premium policies with waived zero premium binder status', function () {
    $lead = createCoverageTestLead();

    $quote = Quote::create([
        'subject' => 'Oscar Bronze Simple $0',
        'user_id' => 1,
        'person_id' => $lead->person_id,
        'carrier_name' => 'Oscar',
        'plan_name' => 'Simple Bronze',
        'metal_tier' => 'bronze',
        'network_type' => 'EPO',
        'gross_premium' => 380.00,
        'aptc_subsidy' => 380.00,
        'net_premium' => 0.00, // Fully subsidized plan
        'effective_date' => Carbon::now()->addMonth()->startOfMonth(),
        'status' => 'approved',
    ]);

    $policy = InsurancePolicy::createOrUpdateFromQuote($quote, $lead->id, 'POL-OSC-300400');

    expect($policy->status)->toBe('active');
    expect($policy->effectuation_status)->toBe('effectuated');
    expect($policy->binder_payment_status)->toBe('waived_zero_premium');
    expect($policy->binder_paid_at)->not->toBeNull();

    $history = PolicyCoverageStatusHistory::where('policy_id', $policy->id)->latest()->first();
    expect($history->to_status)->toBe('active');
    expect($history->reason)->toContain('$0 net premium');
});

it('effectuates coverage when binder payment is recorded with confirmation number', function () {
    $lead = createCoverageTestLead();

    $policy = InsurancePolicy::create([
        'policy_number' => 'POL-TEST-998877',
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'carrier_name' => 'Molina Healthcare',
        'plan_name' => 'Constant Care Silver',
        'metal_tier' => 'silver',
        'network_type' => 'HMO',
        'gross_premium' => 500.00,
        'aptc_subsidy' => 460.00,
        'net_premium' => 40.00,
        'status' => 'binder_pending',
        'effectuation_status' => 'pending_binder',
        'binder_payment_status' => 'pending',
    ]);

    $policy->recordBinderPayment(
        confirmationNumber: 'MOL-REC-776655',
        paymentMethod: 'carrier_portal',
        notes: 'Client completed initial payment via Molina payment portal with agent on phone.'
    );

    $policy->refresh();
    expect($policy->status)->toBe('active');
    expect($policy->effectuation_status)->toBe('effectuated');
    expect($policy->binder_payment_status)->toBe('paid');
    expect($policy->binder_confirmation_number)->toBe('MOL-REC-776655');
    expect($policy->binder_payment_method)->toBe('carrier_portal');
    expect($policy->binder_paid_at)->not->toBeNull();

    // Verify audit history trail recorded
    $histories = $policy->coverageHistories()->get();
    expect($histories)->not->toBeEmpty();
    $latest = $histories->sortByDesc('id')->first();
    expect($latest->from_status)->toBe('binder_pending');
    expect($latest->to_status)->toBe('active');
    expect($latest->reason)->toContain('MOL-REC-776655');
});

it('records binder payment and retrieves coverage history via Book of Business API', function () {
    $admin = getDefaultAdmin();
    $lead = createCoverageTestLead();

    $policy = InsurancePolicy::create([
        'policy_number' => 'POL-API-112233',
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'BlueSelect Silver',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'gross_premium' => 520.00,
        'aptc_subsidy' => 490.00,
        'net_premium' => 30.00,
        'status' => 'binder_pending',
        'effectuation_status' => 'pending_binder',
        'binder_payment_status' => 'pending',
    ]);

    // 1. Submit Binder Payment via Controller endpoint
    $response = test()->actingAs($admin)
        ->postJson(route('admin.policies.record_binder_payment', $policy->id), [
            'confirmation_number' => 'FLB-CONF-882199',
            'payment_method' => 'credit_card',
            'notes' => 'Customer paid first month premium with debit card',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $policy->refresh();
    expect($policy->status)->toBe('active');
    expect($policy->binder_confirmation_number)->toBe('FLB-CONF-882199');

    // 2. Query Coverage History endpoint
    $historyResponse = test()->actingAs($admin)
        ->getJson(route('admin.policies.coverage_history', $policy->id));

    $historyResponse->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'policy' => ['id', 'policy_number', 'status', 'effectuation_status'],
            'history' => [
                '*' => [
                    'id',
                    'from_status',
                    'to_status',
                    'reason',
                    'created_at',
                ],
            ],
        ]);
});
