<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function seedTestPortfolio(): array
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Analytics Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);

    $person1 = Person::create([
        'name' => 'Carlos Valderrama',
        'emails' => [['value' => 'carlos@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3051112233', 'label' => 'mobile']],
    ]);

    $lead1 = Lead::create([
        'title' => 'Carlos Valderrama Policy',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person1->id,
        'user_id' => $admin->id,
    ]);

    // Active Policy 1: Florida Blue Silver, 3 lives, $600 gross
    $policy1 = InsurancePolicy::create([
        'policy_number' => 'POL-FLB-VAL-101',
        'portal_token' => Str::random(40),
        'lead_id' => $lead1->id,
        'person_id' => $person1->id,
        'user_id' => $admin->id,
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'gross_premium' => 600.00,
        'net_premium' => 50.00,
        'members_count' => 3,
        'status' => 'active',
        'effective_date' => Carbon::now()->subMonths(4)->toDateString(),
        'paid_to_date' => Carbon::now()->addDays(20)->toDateString(),
    ]);

    // Active Policy 2: Ambetter Bronze, 1 life, $300 gross
    $policy2 = InsurancePolicy::create([
        'policy_number' => 'POL-AMB-VAL-102',
        'portal_token' => Str::random(40),
        'lead_id' => $lead1->id,
        'person_id' => $person1->id,
        'user_id' => $admin->id,
        'carrier_name' => 'Ambetter',
        'plan_name' => 'Essential Care Bronze',
        'metal_tier' => 'bronze',
        'network_type' => 'HMO',
        'gross_premium' => 300.00,
        'net_premium' => 15.00,
        'members_count' => 1,
        'status' => 'active',
        'effective_date' => Carbon::now()->subMonths(6)->toDateString(),
        'paid_to_date' => Carbon::now()->addDays(15)->toDateString(),
    ]);

    return [$policy1, $policy2];
}

it('displays the executive analytics & book of business valuation dashboard', function () {
    seedTestPortfolio();
    $admin = User::first();

    $response = $this->actingAs($admin)
        ->get(route('admin.insurance.analytics.index'));

    $response->assertOk()
        ->assertSee('Analítica Ejecutiva &amp; Valuación de Cartera', false)
        ->assertSee('Valuación Conservadora')
        ->assertSee('Valor de Mercado Estándar')
        ->assertSee('Florida Blue')
        ->assertSee('Ambetter');
});

it('calculates valuation multiples, ARR, and carrier distributions in JSON endpoint', function () {
    seedTestPortfolio();
    $admin = User::first();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.insurance.analytics.data'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $data = $response->json('metrics');

    // 4 active covered lives * $25 PMPM = $100/month recurring -> $1,200 ARR
    expect($data['valuation']['active_policies'])->toBeGreaterThanOrEqual(2);
    expect($data['valuation']['covered_lives'])->toBeGreaterThanOrEqual(4);
    expect($data['valuation']['monthly_gross_premium'])->toBeGreaterThanOrEqual(900.0);
    expect($data['valuation']['annualized_gross_premium'])->toBeGreaterThanOrEqual(10800.0);

    // Multiples check
    $arr = $data['valuation']['annual_recurring_revenue'];
    expect($data['valuation']['valuation_conservative'])->toEqual(round($arr * 1.5, 2));
    expect($data['valuation']['valuation_standard'])->toEqual(round($arr * 2.0, 2));
    expect($data['valuation']['valuation_aggressive'])->toEqual(round($arr * 2.5, 2));

    // Carrier distribution check
    expect(count($data['carrier_distribution']))->toBeGreaterThanOrEqual(2);
});

it('exports the executive portfolio and valuation report as CSV', function () {
    seedTestPortfolio();
    $admin = User::first();

    $response = $this->actingAs($admin)
        ->get(route('admin.insurance.analytics.export_csv'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('INFORME EJECUTIVO DE VALUACION DE CARTERA')
        ->toContain('Valuacion Mercado Estandar (2.0x ARR)')
        ->toContain('Florida Blue')
        ->toContain('RANKING DE PRODUCTORES');
});
