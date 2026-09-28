<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\RevenueShieldService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createRevenueShieldTestPolicy(string $policyNumber, string $carrier, string $status, ?Carbon $effectiveDate = null): InsurancePolicy
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'General Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Carlos Revenue Client',
        'emails' => [['value' => 'carlos.rev.'.uniqid().'@test.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055558877', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Carlos Revenue Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
    ]);

    return InsurancePolicy::create([
        'policy_number' => $policyNumber,
        'carrier_name' => $carrier,
        'plan_name' => 'Silver Value Care',
        'status' => $status,
        'effective_date' => $effectiveDate ?? Carbon::today()->subDays(60),
        'members_count' => 1,
        'net_premium' => 0.00,
        'lead_id' => $lead->id,
        'user_id' => $admin->id,
    ]);
}

it('flags active policies over 45 days without reconciled commissions', function () {
    $policyNumber = 'POL-UNPAID-'.uniqid();
    $policy = createRevenueShieldTestPolicy($policyNumber, 'Ambetter', 'active', Carbon::today()->subDays(75));

    $service = app(RevenueShieldService::class);
    $results = $service->runAudit();

    $freshPolicy = $policy->fresh();

    expect($freshPolicy->missing_commission_flag)->toBeTrue();
    expect($freshPolicy->missing_commission_amount)->toBeGreaterThan(0);
    expect($freshPolicy->missing_commission_days)->toBeGreaterThanOrEqual(75);
    expect($freshPolicy->missing_commission_detected_at)->not->toBeNull();
    expect($results['flagged_missing'])->toBeGreaterThanOrEqual(1);
    expect($results['carrier_breakdown']['Ambetter']['count'])->toBeGreaterThanOrEqual(1);
});

it('does not flag active policy if reconciled commission exists within 60 days', function () {
    $policyNumber = 'POL-PAID-'.uniqid();
    $policy = createRevenueShieldTestPolicy($policyNumber, 'Florida Blue', 'active', Carbon::today()->subDays(75));

    $admin = User::first();

    // Create a paid commission item
    InsuranceCommission::create([
        'user_id' => $admin->id,
        'policy_number' => $policyNumber,
        'carrier_name' => 'Florida Blue',
        'rate_per_member' => 28.00,
        'status' => 'paid',
        'created_at' => Carbon::today()->subDays(15),
    ]);

    $service = app(RevenueShieldService::class);
    $service->runAudit();

    expect($policy->fresh()->missing_commission_flag)->toBeFalse();
});

it('resolves missing commission flag manually with claim ticket', function () {
    $admin = User::first();
    $this->actingAs($admin);

    $policyNumber = 'POL-DISPUTE-'.uniqid();
    $policy = createRevenueShieldTestPolicy($policyNumber, 'Oscar Health', 'active', Carbon::today()->subDays(60));
    $policy->update([
        'missing_commission_flag' => true,
        'missing_commission_amount' => 56.00,
    ]);

    $response = $this->postJson(route('admin.insurance.revenue_shield.resolve', $policy->id), [
        'resolution_note' => 'Carrier confirmó que incluirá el retroactivo en el próximo ciclo de comisiones.',
        'claim_ticket' => 'OSCAR-CLAIM-9921',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $freshPolicy = $policy->fresh();
    expect($freshPolicy->missing_commission_flag)->toBeFalse();
    expect($freshPolicy->missing_commission_notes)->toContain('OSCAR-CLAIM-9921');
});
