<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Lead\Models\AgencyHierarchy;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\PolicyOverrideDistribution;
use Webkul\Lead\Services\AgencyOverrideService;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestAgent(string $name, string $email): User
{
    $role = Role::first() ?: Role::create(['name' => 'Agent', 'permission_type' => 'all']);

    return User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt('password123'),
        'role_id' => $role->id,
        'status' => 1,
    ]);
}

it('builds multi-level upline chains and calculates downline tree', function () {
    $mga = createTestAgent('Master MGA Lead', 'mga_lead@testagency.com');
    $ga = createTestAgent('General Agent Bob', 'ga_bob@testagency.com');
    $producer = createTestAgent('Producer Charlie', 'producer_charlie@testagency.com');

    // MGA is root
    AgencyHierarchy::create([
        'user_id' => $mga->id,
        'parent_user_id' => null,
        'agency_tier' => 'mga',
        'sub_agency_name' => 'Apex Agency HQ',
        'override_pmpm' => 2.00,
    ]);

    // GA under MGA
    AgencyHierarchy::create([
        'user_id' => $ga->id,
        'parent_user_id' => $mga->id,
        'agency_tier' => 'ga',
        'sub_agency_name' => 'South Florida Branch',
        'override_pmpm' => 3.00,
    ]);

    // Producer under GA
    AgencyHierarchy::create([
        'user_id' => $producer->id,
        'parent_user_id' => $ga->id,
        'agency_tier' => 'producer',
        'override_pmpm' => 4.00,
    ]);

    // Verify upline chain from Producer
    $chain = AgencyHierarchy::getUplineChain($producer->id);

    expect($chain)->toHaveCount(2);
    // Level 1: GA Bob
    expect($chain[0]['beneficiary_user_id'])->toBe($ga->id);
    expect($chain[0]['override_pmpm'])->toBe(4.00);
    // Level 2: MGA Lead
    expect($chain[1]['beneficiary_user_id'])->toBe($mga->id);
    expect($chain[1]['override_pmpm'])->toBe(3.00);

    // Verify hierarchy tree
    $service = app(AgencyOverrideService::class);
    $tree = $service->getHierarchyTree();

    expect($tree)->toBeArray();
    expect(count($tree))->toBeGreaterThan(0);
});

it('automatically distributes multi-tier overrides when an agent writes a policy', function () {
    $mga = createTestAgent('Florida MGA HQ', 'mga_hq@test.com');
    $ga = createTestAgent('Orlando GA Partner', 'orlando_ga@test.com');
    $producer = createTestAgent('Agent Carlos', 'carlos_producer@test.com');

    AgencyHierarchy::create([
        'user_id' => $mga->id,
        'parent_user_id' => null,
        'agency_tier' => 'mga',
        'override_pmpm' => 2.00,
    ]);

    AgencyHierarchy::create([
        'user_id' => $ga->id,
        'parent_user_id' => $mga->id,
        'agency_tier' => 'ga',
        'override_pmpm' => 3.00,
    ]);

    AgencyHierarchy::create([
        'user_id' => $producer->id,
        'parent_user_id' => $ga->id,
        'agency_tier' => 'producer',
        'override_pmpm' => 5.00,
    ]);

    // Producer writes a policy for a family of 3 members
    $policy = InsurancePolicy::create([
        'policy_number' => 'POL-AMB-998811',
        'user_id' => $producer->id,
        'carrier_name' => 'Ambetter',
        'plan_name' => 'Silver Care 2026',
        'gross_premium' => 750.00,
        'aptc_subsidy' => 700.00,
        'net_premium' => 50.00,
        'members_count' => 3,
        'status' => 'active',
    ]);

    $service = app(AgencyOverrideService::class);
    $distributions = $service->distributeOverridesForPolicy($policy);

    expect($distributions)->toHaveCount(2);

    // Level 1: GA receives $5.00 * 3 members = $15.00
    $distGA = PolicyOverrideDistribution::where('policy_id', $policy->id)
        ->where('beneficiary_user_id', $ga->id)
        ->first();
    expect($distGA)->not->toBeNull();
    expect($distGA->override_amount)->toBe(15.00);
    expect($distGA->members_count)->toBe(3);

    // Level 2: MGA receives $3.00 * 3 members = $9.00
    $distMGA = PolicyOverrideDistribution::where('policy_id', $policy->id)
        ->where('beneficiary_user_id', $mga->id)
        ->first();
    expect($distMGA)->not->toBeNull();
    expect($distMGA->override_amount)->toBe(9.00);
});

it('allows admin to save hierarchy configuration and export monthly overrides statement', function () {
    $admin = getDefaultAdmin();
    $agent = createTestAgent('Maria Consultant', 'maria.c@test.com');

    $response = test()->actingAs($admin)
        ->postJson(route('admin.hierarchy.save'), [
            'user_id' => $agent->id,
            'agency_tier' => 'ga',
            'sub_agency_name' => 'Tampa Elite Branch',
            'npn_number' => '20194852',
            'override_pmpm' => 3.50,
            'contract_level_percentage' => 75.0,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $hierarchy = AgencyHierarchy::where('user_id', $agent->id)->first();
    expect($hierarchy)->not->toBeNull();
    expect($hierarchy->sub_agency_name)->toBe('Tampa Elite Branch');
    expect($hierarchy->override_pmpm)->toBe(3.50);

    // Test export
    $exportResponse = test()->actingAs($admin)
        ->get(route('admin.hierarchy.export', ['period' => Carbon::now()->format('Y-m')]));

    $exportResponse->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
