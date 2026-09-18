<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Quote\Models\Quote;

uses(DatabaseTransactions::class);

it('allows admin to see the commissions dashboard and KPIs', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.commissions.index'))
        ->assertOk()
        ->assertSee('Comisiones de Seguros');
});

it('allows admin to create an insurance commission and calculates splits accurately', function () {
    $admin = getDefaultAdmin();

    $response = test()->actingAs($admin)->postJson(route('admin.commissions.store'), [
        'carrier_name' => 'Florida Blue',
        'user_id' => $admin->id,
        'policy_number' => 'FLB-883921',
        'plan_name' => 'BlueOptions Silver',
        'metal_tier' => 'Silver',
        'members_count' => 3, // 3 members
        'rate_per_member' => 28.0, // $28/member
        'agent_split_percentage' => 70.0, // 70% agent, 30% agency
        'effective_date' => '2026-02-01',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $comm = InsuranceCommission::where('policy_number', 'FLB-883921')->first();
    expect($comm)->not->toBeNull();
    // 3 members * $28 = $84.00 gross
    expect((float) $comm->gross_monthly)->toBe(84.0);
    // 70% of $84 = $58.80 agent net
    expect((float) $comm->agent_monthly)->toBe(58.8);
    // 30% of $84 = $25.20 agency net
    expect((float) $comm->agency_monthly)->toBe(25.2);
});

it('automatically creates insurance commission when a health quote is converted to policy', function () {
    $admin = getDefaultAdmin();

    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);
    $person = Person::create(['name' => 'Lucia Fernandez']);

    $lead = Lead::create([
        'title' => 'Lucia Fernandez - Health',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
    ]);

    $quote = Quote::create([
        'subject' => 'Propuesta Salud - Ambetter Gold',
        'carrier_name' => 'Ambetter (Sunshine State)',
        'plan_name' => 'Ambetter Secure Care Gold',
        'metal_tier' => 'Gold',
        'gross_premium' => 700.0,
        'aptc_subsidy' => 690.0,
        'net_premium' => 10.0,
        'quote_status' => 'presented',
        'user_id' => $admin->id,
        'person_id' => $person->id,
        'sub_total' => 700.0,
        'discount_amount' => 690.0,
        'grand_total' => 10.0,
    ]);

    $quote->leads()->attach($lead->id);

    // Call convert_to_policy
    $response = test()->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', ['id' => $quote->id]));
    $response->assertOk();

    // Verify InsuranceCommission was generated
    $comm = InsuranceCommission::where('quote_id', $quote->id)->first();
    expect($comm)->not->toBeNull();
    expect($comm->carrier_name)->toBe('Ambetter (Sunshine State)');
    expect($comm->lead_id)->toBe($lead->id);
    expect($comm->user_id)->toBe($admin->id);
    expect($comm->status)->toBe('active');
    expect((float) $comm->rate_per_member)->toBeGreaterThan(0);
});
