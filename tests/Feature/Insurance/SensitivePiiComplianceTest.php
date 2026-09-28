<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createPiiTestLead(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'PII Test Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Carlos Delgado',
        'emails' => [['value' => 'carlos.delgado@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865557788', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Carlos Delgado - ACA Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('encrypts sensitive SSN/ITIN in the database and decrypts it on model access', function () {
    $lead = createPiiTestLead();

    $member = HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Carlos Delgado',
        'relationship' => 'primary',
        'date_of_birth' => '1985-04-12',
        'gender' => 'male',
        'ssn_itin' => '123-45-6789',
        'immigration_status' => 'citizen',
    ]);

    // 1. Raw database value must be encrypted (not plaintext)
    $rawSsn = $member->getRawOriginal('ssn_itin');
    expect($rawSsn)->not->toBe('123-45-6789');
    expect(Crypt::decryptString($rawSsn))->toBe('123-45-6789');

    // 2. Model accessor automatically decrypts it
    $freshMember = HouseholdMember::find($member->id);
    expect($freshMember->ssn_itin)->toBe('123-45-6789');
});

it('formats masked SSN correctly for secure UI rendering', function () {
    $lead = createPiiTestLead();

    $member = HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Elena Delgado',
        'relationship' => 'spouse',
        'ssn_itin' => '987-65-4321',
    ]);

    expect($member->masked_ssn)->toBe('***-**-4321');

    // Test 9 digits without dashes
    $member->ssn_itin = '999887766';
    $member->save();
    $member->refresh();

    expect($member->masked_ssn)->toBe('***-**-7766');
});

it('gracefully handles legacy unencrypted SSN without throwing DecryptException', function () {
    $lead = createPiiTestLead();

    $member = new HouseholdMember;
    $member->setRawAttributes([
        'lead_id' => $lead->id,
        'name' => 'Legacy Member',
        'relationship' => 'dependent',
        'ssn_itin' => '555-44-3322',
    ]);
    $member->save();

    $loaded = HouseholdMember::find($member->id);
    expect($loaded->ssn_itin)->toBe('555-44-3322');
    expect($loaded->masked_ssn)->toBe('***-**-3322');
});

it('returns masked SSN in household member index API by default', function () {
    $admin = getDefaultAdmin();
    $lead = createPiiTestLead();

    HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Sofia Delgado',
        'relationship' => 'dependent',
        'ssn_itin' => '111-22-3333',
    ]);

    $response = test()->actingAs($admin)
        ->getJson(route('admin.leads.household.index', $lead->id));

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'can_view_pii',
        ]);

    $members = $response->json('data');
    expect($members)->toHaveCount(1);
    expect($members[0]['ssn_itin'])->toBe('***-**-3333');
    expect($members[0]['masked_ssn'])->toBe('***-**-3333');
});

it('allows authorized admin with permission to reveal decrypted SSN and prevents unauthorized access', function () {
    $admin = getDefaultAdmin();
    $lead = createPiiTestLead();

    $member = HouseholdMember::create([
        'lead_id' => $lead->id,
        'name' => 'Carlos Delgado',
        'relationship' => 'primary',
        'ssn_itin' => '123-45-6789',
    ]);

    // Authorized request (Default admin has full permissions)
    $response = test()->actingAs($admin)
        ->getJson(route('admin.leads.household.reveal_pii', ['lead_id' => $lead->id, 'id' => $member->id]));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'full_ssn' => '123-45-6789',
            'masked_ssn' => '***-**-6789',
        ]);

    // Restricted agent without leads.view_sensitive_pii permission
    $restrictedRole = Role::create([
        'name' => 'Restricted Viewer',
        'description' => 'Role without PII view access',
        'permission_type' => 'custom',
        'permissions' => ['leads.view'],
    ]);

    $restrictedUser = User::create([
        'name' => 'Restricted Agent',
        'email' => 'restricted.'.uniqid().'@example.com',
        'password' => bcrypt('password123'),
        'role_id' => $restrictedRole->id,
        'status' => 1,
    ]);

    test()->actingAs($restrictedUser)
        ->getJson(route('admin.leads.household.reveal_pii', ['lead_id' => $lead->id, 'id' => $member->id]))
        ->assertForbidden();
});
