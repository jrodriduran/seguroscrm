<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadTaxHousehold;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\FplCalculatorService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createFplTestLead(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'FPL Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Quote Generated',
        'code' => 'quote_generated',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Yusdelis Martinez',
        'emails' => [['value' => 'yusdelis@example.com', 'label' => 'home']],
        'contact_numbers' => [['value' => '3055554321', 'label' => 'mobile']],
        'address' => 'Miami, FL 33125',
    ]);

    $user = User::first() ?: User::factory()->create();

    return Lead::create([
        'title' => 'Yusdelis Martinez - Family ACA',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $user->id,
    ]);
}

it('calculates FPL and CSR tier accurately according to HHS official guidelines and ACA rules', function () {
    $service = new FplCalculatorService;

    // Test Case: Florida family of 3 with $35,000 income (Year 2025: threshold $26,650 -> 131.3% FPL)
    $res2025 = $service->calculate(
        householdSize: 3,
        annualIncome: 35000.0,
        stateCode: 'FL',
        taxYear: 2025
    );

    expect($res2025['fpl_guideline_threshold'])->toBe(26650.0);
    expect($res2025['fpl_percentage'])->toBe(131.3);
    expect($res2025['fpl_category'])->toBe('silver_94');
    expect($res2025['csr_tier'])->toContain('Silver CSR 94%');
    expect($res2025['applicable_percentage'])->toBe(0.0);
    expect($res2025['max_monthly_contribution'])->toBe(0.0);
    expect($res2025['estimated_net_premium'])->toBe(0.0);
    expect($res2025['is_zero_premium_eligible'])->toBeTrue();

    // Test Case: Year 2026 (threshold $27,160 -> 128.9% FPL)
    $res2026 = $service->calculate(
        householdSize: 3,
        annualIncome: 35000.0,
        stateCode: 'FL',
        taxYear: 2026
    );

    expect($res2026['fpl_guideline_threshold'])->toBe(27160.0);
    expect($res2026['fpl_percentage'])->toBe(128.9);
    expect($res2026['fpl_category'])->toBe('silver_94');
    expect($res2026['is_zero_premium_eligible'])->toBeTrue();

    // Test Case: Individual with $28,000 income (~175% FPL -> Silver 87%)
    $res87 = $service->calculate(
        householdSize: 1,
        annualIncome: 28000.0,
        stateCode: 'FL',
        taxYear: 2026
    );
    expect($res87['fpl_category'])->toBe('silver_87');
    expect($res87['csr_tier'])->toContain('Silver CSR 87%');

    // Test Case: Family of 2 with $50,000 income (~230% FPL -> Silver 73%)
    $res73 = $service->calculate(
        householdSize: 2,
        annualIncome: 50000.0,
        stateCode: 'FL',
        taxYear: 2026
    );
    expect($res73['fpl_category'])->toBe('silver_73');
    expect($res73['csr_tier'])->toContain('Silver CSR 73%');

    // Test Case: Income below 100% FPL in non-expansion state -> Medicaid Gap
    $resGap = $service->calculate(
        householdSize: 1,
        annualIncome: 12000.0,
        stateCode: 'FL',
        taxYear: 2026
    );
    expect($resGap['fpl_category'])->toBe('medicaid_gap');
    expect($resGap['estimated_monthly_aptc'])->toBe(0.0);
});

it('fetches, previews and persists tax household FPL calculations via API endpoints', function () {
    $lead = createFplTestLead();
    $user = User::first() ?: User::factory()->create();

    // Add 2 household dependents (titular + 2 = household size 3)
    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Roberto Martinez',
        'relationship' => 'spouse',
        'date_of_birth' => '1984-06-10',
        'is_applying_coverage' => true,
    ]);

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Sofia Martinez',
        'relationship' => 'child',
        'date_of_birth' => '2016-09-02',
        'is_applying_coverage' => true,
    ]);

    // 1. Preview on-the-fly without saving
    $previewResponse = $this->actingAs($user)->postJson(
        route('admin.leads.household.fpl.preview', ['lead_id' => $lead->id]),
        [
            'projected_annual_income' => 35000,
            'household_size' => 3,
            'tax_year' => 2025,
            'state_code' => 'FL',
        ]
    );

    $previewResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('calculation.fpl_percentage', 131.3)
        ->assertJsonPath('calculation.fpl_category', 'silver_94')
        ->assertJsonPath('calculation.is_zero_premium_eligible', true);

    // Verify DB still empty for lead_tax_households
    expect(LeadTaxHousehold::where('lead_id', $lead->id)->count())->toBe(0);

    // 2. Save FPL eligibility to database
    $saveResponse = $this->actingAs($user)->postJson(
        route('admin.leads.household.fpl.save', ['lead_id' => $lead->id]),
        [
            'projected_annual_income' => 35000,
            'household_size' => 3,
            'tax_year' => 2025,
            'state_code' => 'FL',
            'notes' => 'W2 Income projected for ACA tax credit',
        ]
    );

    $saveResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('tax_household.fpl_percentage', 131.3)
        ->assertJsonPath('tax_household.fpl_category', 'silver_94');

    // Verify record persisted in database
    $stored = LeadTaxHousehold::where('lead_id', $lead->id)->where('tax_year', 2025)->first();
    expect($stored)->not->toBeNull();
    expect($stored->projected_annual_income)->toBe(35000.0);
    expect($stored->household_size)->toBe(3);
    expect($stored->fpl_category)->toBe('silver_94');
    expect($stored->csr_label)->toContain('Silver CSR 94%');

    // 3. Verify lead relationship access
    $lead->refresh();
    expect($lead->taxHouseholds)->toHaveCount(1);
    expect($lead->taxHousehold?->fpl_category)->toBe('silver_94');

    // 4. Fetch via GET endpoint
    $getResponse = $this->actingAs($user)->getJson(
        route('admin.leads.household.fpl', ['lead_id' => $lead->id, 'tax_year' => 2025])
    );

    $getResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('has_saved_record', true)
        ->assertJsonPath('tax_household.fpl_percentage', 131.3)
        ->assertJsonPath('calculation.csr_tier', $stored->csr_tier);
});
