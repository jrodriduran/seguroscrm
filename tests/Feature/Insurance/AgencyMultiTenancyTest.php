<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Webkul\Lead\Models\Agency;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

it('isolates leads and policies between distinct agencies using agency global scope', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Agency Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    // 1. Create Agency A & User A
    $agencyA = Agency::create([
        'name' => 'Alpha Health Agency',
        'code' => 'ALPHA_'.uniqid(),
        'status' => 'active',
    ]);

    $userA = User::create([
        'name' => 'Agent Alpha',
        'email' => 'agent.alpha.'.uniqid().'@example.com',
        'password' => Hash::make('password123'),
        'status' => 1,
        'role_id' => 2,
        'agency_id' => $agencyA->id,
    ]);

    // 2. Create Agency B & User B
    $agencyB = Agency::create([
        'name' => 'Beta Insurance Group',
        'code' => 'BETA_'.uniqid(),
        'status' => 'active',
    ]);

    $userB = User::create([
        'name' => 'Agent Beta',
        'email' => 'agent.beta.'.uniqid().'@example.com',
        'password' => Hash::make('password123'),
        'status' => 1,
        'role_id' => 2,
        'agency_id' => $agencyB->id,
    ]);

    // 3. User A creates lead and policy for Agency A
    $this->actingAs($userA);

    $leadA = Lead::create([
        'title' => 'Alpha Secret Client - Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'user_id' => $userA->id,
    ]);

    $policyA = InsurancePolicy::create([
        'policy_number' => 'POL-ALPHA-'.uniqid(),
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'Silver HMO',
        'status' => 'active',
        'user_id' => $userA->id,
    ]);

    expect($leadA->agency_id)->toEqual($agencyA->id);
    expect($policyA->agency_id)->toEqual($agencyA->id);

    // When User A queries, finds both records
    expect(Lead::where('id', $leadA->id)->exists())->toBeTrue();
    expect(InsurancePolicy::where('id', $policyA->id)->exists())->toBeTrue();

    // 4. Switch context to User B (Agency B)
    $this->actingAs($userB);

    // User B must NOT see Agency A lead or policy
    expect(Lead::where('id', $leadA->id)->exists())->toBeFalse();
    expect(InsurancePolicy::where('id', $policyA->id)->exists())->toBeFalse();

    // User B creates Agency B lead
    $leadB = Lead::create([
        'title' => 'Beta Secret Client - Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'user_id' => $userB->id,
    ]);

    expect($leadB->agency_id)->toEqual($agencyB->id);
    expect(Lead::where('id', $leadB->id)->exists())->toBeTrue();

    // Switch back to User A -> User A cannot see Agency B lead
    $this->actingAs($userA);
    expect(Lead::where('id', $leadB->id)->exists())->toBeFalse();
});
