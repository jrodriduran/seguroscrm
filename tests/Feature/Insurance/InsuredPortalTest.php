<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestPolicyWithPortal(): InsurancePolicy
{
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Portal Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Jorge Sanchez',
        'emails' => [['value' => 'jorge.sanchez@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865553344', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Jorge Sanchez - Florida Blue',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Valentina Sanchez',
        'relationship' => 'child',
        'date_of_birth' => Carbon::today()->subYears(8)->toDateString(),
        'gender' => 'female',
        'is_applying_coverage' => true,
    ]);

    return InsurancePolicy::create([
        'policy_number' => 'POL-FLB-554433',
        'portal_token' => Str::random(40),
        'member_id' => 'MBR-88441122',
        'group_number' => 'GRP-FLB-9900',
        'lead_id' => $lead->id,
        'person_id' => $person->id,
        'user_id' => 1,
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver 1410',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'gross_premium' => 580.00,
        'aptc_subsidy' => 540.00,
        'net_premium' => 40.00,
        'effective_date' => Carbon::now()->startOfMonth(),
        'status' => 'active',
        'members_count' => 2,
    ]);
}

it('displays the digital insurance wallet card and carrier contacts on the client portal', function () {
    $policy = createTestPolicyWithPortal();

    test()->get(route('insured.portal.show', $policy->portal_token))
        ->assertOk()
        ->assertSee('PORTAL DEL ASEGURADO')
        ->assertSee('Jorge Sanchez')
        ->assertSee('POL-FLB-554433')
        ->assertSee('MBR-88441122')
        ->assertSee('FLORIDA BLUE')
        ->assertSee('Valentina Sanchez')
        ->assertSee('1-800-352-2583');
});

it('downloads printable PDF digital insurance ID wallet card', function () {
    $policy = createTestPolicyWithPortal();

    test()->get(route('insured.portal.download_card', $policy->portal_token))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('allows insured client to upload verification document from self-service portal', function () {
    Storage::fake('public');

    $policy = createTestPolicyWithPortal();
    $fakeFile = UploadedFile::fake()->create('proof_of_income.pdf', 150, 'application/pdf');

    $response = test()->postJson(route('insured.portal.upload_doc', $policy->portal_token), [
        'doc_type' => 'income',
        'document' => $fakeFile,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $dmi = LeadDmiDocument::where('lead_id', $policy->lead_id)->first();
    expect($dmi)->not->toBeNull();
    expect($dmi->doc_type)->toBe('income');
    expect($dmi->status)->toBe('submitted_to_marketplace');
    expect($dmi->notes)->toContain('autoservicio');
});
