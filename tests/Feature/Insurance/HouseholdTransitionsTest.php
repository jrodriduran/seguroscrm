<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Insurance\Models\HouseholdTransition;
use Webkul\Insurance\Services\HouseholdTransitions;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function familyScenario(): array
{
    $admin = getDefaultAdmin();
    $person = Person::create(['name' => 'Synthetic Parent']);
    $pipeline = Pipeline::firstOrFail();
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->firstOrFail();
    $lead = Lead::create([
        'title' => 'Synthetic family', 'person_id' => $person->id, 'user_id' => $admin->id,
        'lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $stage->id,
    ]);
    $member = $lead->householdMembers()->create([
        'name' => 'Synthetic Child', 'relationship' => 'child', 'date_of_birth' => '2000-12-20',
        'ssn_itin' => '111-22-3333', 'is_applying_coverage' => true,
    ]);
    $data = ['member_id' => $member->id, 'reason' => 'age_limit', 'plan_type' => 'marketplace', 'coverage_end_date' => '2026-12-31', 'review_on' => '2026-11-01', 'note' => 'Synthetic plan review'];
    $url = route('admin.leads.family_transitions.index', $lead->id);

    return compact('admin', 'lead', 'member', 'data', 'url');
}

function plannedFamilyTransition(array $case): HouseholdTransition
{
    $data = $case['data'];
    unset($data['member_id']);

    return app(HouseholdTransitions::class)->plan($case['lead'], $case['member']->id, $data, $case['admin']->id);
}

it('plans a family transition with a responsible follow-up without changing coverage or tax data', function () {
    extract(familyScenario());
    $this->actingAs($admin)->postJson($url, $data)->assertCreated()->assertJsonPath('data.status', 'planned');
    expect($member->fresh()->is_applying_coverage)->toBeTrue();
    expect($lead->taxHouseholds()->count())->toBe(0);
    $task = FollowUp::where('entity_id', $lead->id)->where('source', 'like', 'household-transition:%')->sole();
    expect($task->assigned_to)->toBe($admin->id)->and($task->status)->toBe('open');
    Http::assertNothingSent();
});

it('does not duplicate an open transition or its task', function () {
    extract(familyScenario());
    $this->actingAs($admin)->postJson($url, $data)->assertCreated();
    $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('member_id');
    expect(HouseholdTransition::where('lead_id', $lead->id)->count())->toBe(1);
});

it('requires a member from the current family', function () {
    extract(familyScenario());
    $foreign = familyScenario()['member'];
    $data['member_id'] = $foreign->id;
    $this->actingAs($admin)->postJson($url, $data)->assertNotFound();
    expect(HouseholdTransition::where('lead_id', $lead->id)->count())->toBe(0);
});

it('rejects missing or inconsistent transition details', function ($field, $value) {
    extract(familyScenario());
    $data[$field] = $value;
    $this->actingAs($admin)->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([['reason', 'unknown'], ['note', ''], ['review_on', '2027-01-01'], ['coverage_end_date', 'not-a-date']]);

it('records confirmation or cancellation with an audit reference and closes only its review', function ($status) {
    $case = familyScenario();
    extract($case);
    $item = plannedFamilyTransition($case);
    $request = ['status' => $status, 'confirmation_reference' => 'Synthetic verified reference'];
    $this->actingAs($admin)->putJson($url.'/'.$item->id, $request)->assertOk()->assertJsonPath('data.status', $status);
    $this->putJson($url.'/'.$item->id, $request)->assertOk();
    $this->putJson($url.'/'.$item->id, ['status' => $status, 'confirmation_reference' => 'changed'])->assertUnprocessable();
    expect($item->fresh()->resolved_by)->toBe($admin->id);
    expect(FollowUp::where('source', 'household-transition:'.$item->id)->sole()->status)->toBe('done');
    expect($member->fresh()->is_applying_coverage)->toBeTrue();
    Http::assertNothingSent();
})->with(['confirmed', 'cancelled']);

it('requires an external reference before recording confirmation', function () {
    $case = familyScenario();
    $item = plannedFamilyTransition($case);
    $this->actingAs($case['admin'])->putJson($case['url'].'/'.$item->id, ['status' => 'confirmed'])->assertUnprocessable();
    expect($item->fresh()->status)->toBe('planned');
});

it('creates one linked individual case without copying parental consent income or identifiers', function () {
    $case = familyScenario();
    extract($case);
    $item = plannedFamilyTransition($case);
    $this->actingAs($admin)->postJson($url.'/'.$item->id.'/individual')->assertOk();
    $newId = $item->fresh()->individual_lead_id;
    $this->postJson($url.'/'.$item->id.'/individual')->assertOk()->assertJsonPath('data.individual_lead_id', $newId);
    $individual = Lead::findOrFail($newId);
    expect($individual->person->name)->toBe($member->name);
    expect($individual->person_id)->not->toBe($lead->person_id);
    expect($individual->has_tcpa_consent)->toBeFalse();
    expect($individual->taxHouseholds()->count())->toBe(0);
    expect($individual->householdMembers()->count())->toBe(0);
    expect(FollowUp::where('source', 'household-individual:'.$member->id)->count())->toBe(1);
    $this->get(route('admin.leads.view', $newId))->assertOk();
});

it('does not create an individual case for a death or a cancelled transition', function ($reason, $status) {
    $case = familyScenario();
    $case['data']['reason'] = $reason;
    $item = plannedFamilyTransition($case);
    $item->update(['status' => $status]);
    $this->actingAs($case['admin'])->postJson($case['url'].'/'.$item->id.'/individual')->assertUnprocessable();
})->with([['death', 'planned'], ['age_limit', 'cancelled']]);

it('preserves the member instead of deleting family history', function () {
    extract(familyScenario());
    $this->actingAs($admin)->deleteJson(route('admin.leads.household.delete', [$lead->id, $member->id]))->assertUnprocessable();
    expect(HouseholdMember::find($member->id))->not->toBeNull();
});

it('keeps real identifiers when the existing masked edit form is submitted and masks write responses', function () {
    extract(familyScenario());
    $this->actingAs($admin)->putJson(route('admin.leads.household.update', [$lead->id, $member->id]), [
        'name' => 'Synthetic renamed', 'relationship' => 'child', 'ssn_itin' => '***-**-3333',
    ])->assertOk()->assertJsonPath('data.ssn_itin', '***-**-3333')->assertDontSee('111-22-3333');
    expect($member->fresh()->ssn_itin)->toBe('111-22-3333');
    $this->postJson(route('admin.leads.household.store', $lead->id), [
        'name' => 'Synthetic Spouse', 'relationship' => 'spouse', 'ssn_itin' => '222-33-4444',
    ])->assertOk()->assertDontSee('222-33-4444');
});

it('denies foreign family access across household operations', function ($operation) {
    extract(familyScenario());
    $role = Role::create(['name' => 'Family restricted', 'permission_type' => 'custom', 'permissions' => ['leads', 'leads.edit', 'leads.view_sensitive_pii', 'leads.family_transitions']]);
    $user = User::create(['name' => 'Synthetic agent', 'email' => 'family-agent@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $role->id, 'view_permission' => 'individual']);
    $this->actingAs($user);
    (match ($operation) {
        'list' => $this->getJson(route('admin.leads.household.index', $lead->id)),
        'reveal' => $this->getJson(route('admin.leads.household.reveal_pii', [$lead->id, $member->id])),
        'fpl' => $this->getJson(route('admin.leads.household.fpl', $lead->id)),
        'write' => $this->postJson($url, $data),
    })->assertUnauthorized();
})->with(['list', 'reveal', 'fpl', 'write']);

it('creates one age review task without assuming a coverage end date', function () {
    $this->travelTo(now()->setDate(2026, 10, 15));
    extract(familyScenario());
    $this->artisan('insurance:review-household-ages')->assertSuccessful();
    $this->artisan('insurance:review-household-ages')->assertSuccessful();
    expect(FollowUp::where('source', 'household-age26:'.$member->id)->count())->toBe(1);
    expect(HouseholdTransition::where('lead_id', $lead->id)->count())->toBe(0);
    expect($member->fresh()->is_applying_coverage)->toBeTrue();
    $this->travelBack();
});

it('renders the family transition workflow with resolved translations', function () {
    extract(familyScenario());
    $this->actingAs($admin);
    $html = view('admin::leads.view.family-transitions', ['lead' => $lead])->render();
    expect($html)->toContain('v-family-transitions')->not->toContain('admin::app.family-workflow');
});

it('does not present missing tax data as zero income and a real eligibility result', function () {
    extract(familyScenario());
    $this->actingAs($admin)->getJson(route('admin.leads.household.fpl', $lead->id))
        ->assertOk()->assertJsonPath('has_saved_record', false)->assertJsonPath('calculation', null);
});

it('requires the family permission even for an owned case', function () {
    extract(familyScenario());
    $role = Role::create(['name' => 'Family without permission', 'permission_type' => 'custom', 'permissions' => ['leads']]);
    $user = User::create(['name' => 'Synthetic owner', 'email' => 'family-owner@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $role->id, 'view_permission' => 'individual']);
    $lead->update(['user_id' => $user->id]);
    $this->actingAs($user)->getJson($url)->assertUnauthorized();
});

it('allows a permitted nonadmin to manage their own family but requires lead creation permission for an individual case', function () {
    $case = familyScenario();
    extract($case);
    $role = Role::create(['name' => 'Family permitted', 'permission_type' => 'custom', 'permissions' => ['leads', 'leads.family_transitions']]);
    $user = User::create(['name' => 'Synthetic family owner', 'email' => 'family-permitted@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $role->id, 'view_permission' => 'individual']);
    $lead->update(['user_id' => $user->id]);
    $this->actingAs($user)->postJson($url, $data)->assertCreated();
    $item = HouseholdTransition::where('lead_id', $lead->id)->sole();
    $this->getJson($url)->assertOk();
    $this->postJson($url.'/'.$item->id.'/individual')->assertForbidden();
});

it('blocks a family quote after the planned Marketplace exit until the family composition is reviewed', function () {
    $case = familyScenario();
    extract($case);
    plannedFamilyTransition($case);
    LeadConsent::create([
        'lead_id' => $lead->id, 'person_id' => $lead->person_id, 'user_id' => $admin->id,
        'token' => Str::random(40), 'status' => 'signed',
        'client_name' => 'Synthetic Parent', 'consent_text' => 'Synthetic consent', 'signed_at' => now(),
    ]);
    $payload = [
        'context' => ['product' => 'aca', 'exchange' => 'on_exchange', 'coverage_family' => 'medical', 'coverage_type' => 'medical', 'plan_year' => 2027],
        'location' => ['zip_code' => '33101', 'fips_code' => '12086', 'state' => 'FL'],
        'household' => ['household_size' => 2, 'annual_income' => 41000, 'effective_date' => '2027-01-01', 'applicants' => [
            ['member_id' => 'person-'.$lead->person_id, 'age' => 50, 'relationship' => 'primary', 'uses_tobacco' => false],
            ['member_id' => 'member-'.$member->id, 'age' => 26, 'relationship' => 'dependent', 'uses_tobacco' => false],
        ]],
    ];
    $this->actingAs($admin)->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('household.applicants');
    Http::assertNothingSent();
});

it('reuses the linked individual case across successive transitions for the same member', function () {
    $case = familyScenario();
    extract($case);
    $first = plannedFamilyTransition($case);
    $service = app(HouseholdTransitions::class);
    $first = $service->individualCase($lead, $first->id, $admin->id);
    $service->resolve($lead, $first->id, 'confirmed', 'Synthetic reference', $admin->id);
    $next = plannedFamilyTransition($case);
    $next = $service->individualCase($lead, $next->id, $admin->id);
    expect($next->individual_lead_id)->toBe($first->individual_lead_id);
});
