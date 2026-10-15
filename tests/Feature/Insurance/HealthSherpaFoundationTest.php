<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Http::preventStrayRequests();
    RateLimiter::clear('healthsherpa:requests');
    RateLimiter::clear('healthsherpa:cooldown');
    config(['healthsherpa.enabled' => true, 'healthsherpa.api_key' => 'synthetic-hs-key']);
});

function makeHealthSherpaLead(bool $signed = true): Lead
{
    $admin = getDefaultAdmin();
    $person = Person::create(['name' => 'Synthetic HS Applicant']);
    $pipeline = Pipeline::firstOrFail();
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->firstOrFail();
    $lead = Lead::create([
        'title' => 'Synthetic HS case', 'person_id' => $person->id, 'user_id' => $admin->id,
        'lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $stage->id,
    ]);
    LeadConsent::create([
        'lead_id' => $lead->id, 'person_id' => $person->id, 'user_id' => $admin->id,
        'token' => Str::random(40), 'status' => $signed ? 'signed' : 'pending',
        'client_name' => 'Synthetic HS Applicant', 'consent_text' => 'Synthetic test consent',
        'signed_at' => $signed ? now() : null,
    ]);

    return $lead;
}

function healthSherpaQuoteInput(Lead $lead): array
{
    return [
        'context' => ['product' => 'aca', 'exchange' => 'on_exchange', 'coverage_family' => 'medical', 'coverage_type' => 'medical', 'plan_year' => 2027],
        'location' => ['zip_code' => '33101', 'fips_code' => '12086', 'state' => 'FL'],
        'household' => [
            'household_size' => 1, 'annual_income' => 41000,
            'applicants' => [['member_id' => 'person-'.$lead->person_id, 'age' => 37, 'relationship' => 'primary', 'uses_tobacco' => false]],
        ],
    ];
}

it('reports configuration without exposing keys or implying enrollment approval', function () {
    config(['healthsherpa.enabled' => false]);
    $this->actingAs(getDefaultAdmin())->getJson(route('admin.insurance.healthsherpa.readiness'))
        ->assertOk()->assertJsonPath('data.configuration_ready', false)
        ->assertJsonPath('data.enrollment_available', false)
        ->assertJsonPath('data.connection_verified', false)
        ->assertDontSee('synthetic-hs-key');
    Http::assertNothingSent();
});

it('does not contact HealthSherpa while disabled or missing its key', function ($enabled, $key) {
    config(['healthsherpa.enabled' => $enabled, 'healthsherpa.api_key' => $key]);
    $lead = makeHealthSherpaLead();
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertServiceUnavailable();
    Http::assertNothingSent();
})->with([[false, 'synthetic-hs-key'], [true, null]]);

it('quotes reviewed ACA data using the server key without creating policies', function () {
    $lead = makeHealthSherpaLead();
    $payload = healthSherpaQuoteInput($lead);
    $payload['household']['applicants'][0]['age'] = '37';
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response(['plans' => [['plan_id' => 'synthetic-plan']]], 200)]);

    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertOk()->assertJsonPath('data.plans.0.plan_id', 'synthetic-plan')->assertDontSee('synthetic-hs-key');

    Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'synthetic-hs-key')
        && $request['household']['applicants'][0]['age'] === 37
        && $request['household']['applicants'][0]['uses_tobacco'] === false
        && ! isset($request['household']['applicants'][0]['name']));
    expect(InsurancePolicy::where('lead_id', $lead->id)->exists())->toBeFalse();
});

it('returns county options without selecting a county for the agent', function () {
    Http::fake(['https://api.one.healthsherpa.com/v1/reference/counties*' => Http::response(['counties' => [
        ['fips_code' => '12086', 'name' => 'Synthetic County A', 'state' => 'FL'],
        ['fips_code' => '12087', 'name' => 'Synthetic County B', 'state' => 'FL'],
    ]], 200)]);
    $this->actingAs(getDefaultAdmin())->getJson(route('admin.insurance.healthsherpa.counties', ['zip_code' => '33101']))
        ->assertOk()->assertJsonCount(2, 'data.counties');
    Http::assertSentCount(1);
});

it('rejects incomplete quote fields instead of inventing values', function ($field) {
    $lead = makeHealthSherpaLead();
    $payload = healthSherpaQuoteInput($lead);
    data_forget($payload, $field);
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    Http::assertNothingSent();
})->with(['location.zip_code', 'location.fips_code', 'context.plan_year', 'household.annual_income', 'household.applicants.0.age', 'household.applicants.0.uses_tobacco']);

it('does not send unknown applicant fields such as SSN', function () {
    $lead = makeHealthSherpaLead();
    $payload = healthSherpaQuoteInput($lead);
    $payload['household']['applicants'][0]['ssn'] = 'synthetic-sensitive-value';
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertUnprocessable();
    Http::assertNothingSent();
});

it('requires consent before quoting a client', function () {
    $lead = makeHealthSherpaLead(false);
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertUnprocessable()->assertJsonValidationErrors('consent');
    Http::assertNothingSent();
});

it('rejects applicants unrelated to the client record', function () {
    $lead = makeHealthSherpaLead();
    $payload = healthSherpaQuoteInput($lead);
    $payload['household']['applicants'][0]['member_id'] = 'person-unrelated';
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('household.applicants');
    Http::assertNothingSent();
});

it('rejects multiple primary applicants or a household smaller than its applicants', function () {
    $lead = makeHealthSherpaLead();
    $payload = healthSherpaQuoteInput($lead);
    $payload['household']['applicants'][] = ['member_id' => 'member-test', 'age' => 35, 'relationship' => 'primary', 'uses_tobacco' => false];
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('household.applicants');
    Http::assertNothingSent();
});

it('keeps provider error bodies and credentials out of CRM responses', function ($status, $expected) {
    $lead = makeHealthSherpaLead();
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response(['error' => ['message' => 'synthetic-secret-body']], $status)]);
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertStatus($expected)->assertDontSee('synthetic-secret-body')->assertDontSee('synthetic-hs-key');
})->with([[403, 503], [400, 422], [500, 503]]);

it('honors provider cooldown without automatically retrying', function () {
    $lead = makeHealthSherpaLead();
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response([], 429, ['Retry-After' => '7'])]);
    $url = route('admin.leads.healthsherpa.quotes', $lead->id);
    $payload = healthSherpaQuoteInput($lead);
    $this->actingAs(getDefaultAdmin())->postJson($url, $payload)->assertStatus(429)->assertHeader('Retry-After', '7');
    $this->postJson($url, $payload)->assertStatus(429);
    Http::assertSentCount(1);
});

it('rejects malformed successful provider responses', function () {
    $lead = makeHealthSherpaLead();
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response('invalid json', 200)]);
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertStatus(502);
});

it('handles transport failures without returning request details', function () {
    $lead = makeHealthSherpaLead();
    Http::fake(fn () => throw new ConnectionException('synthetic-private-transport-details'));
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertServiceUnavailable()->assertDontSee('synthetic-private-transport-details');
});

it('limits local request frequency before contacting the provider again', function () {
    Http::fake(['https://api.one.healthsherpa.com/v1/reference/counties*' => Http::response(['counties' => []], 200)]);
    $url = route('admin.insurance.healthsherpa.counties', ['zip_code' => '33101']);
    $this->actingAs(getDefaultAdmin())->getJson($url)->assertOk();
    $this->getJson($url)->assertStatus(429);
    Http::assertSentCount(1);
});

it('does not follow provider redirects to other hosts', function () {
    $lead = makeHealthSherpaLead();
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response('', 302, ['Location' => 'https://unrelated.invalid/'])]);
    $this->actingAs(getDefaultAdmin())->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertServiceUnavailable();
    Http::assertSentCount(1);
});

it('keeps legacy routes safe while enrollment is not approved', function () {
    $lead = makeHealthSherpaLead();
    $this->actingAs(getDefaultAdmin())->getJson(route('admin.leads.healthsherpa.payload', $lead->id))->assertUnprocessable();
    $this->getJson(route('admin.leads.healthsherpa.redirect', $lead->id))->assertServiceUnavailable();
    Http::assertNothingSent();
});

it('enforces the dedicated permission and client ownership before contacting HS', function ($permissions) {
    $lead = makeHealthSherpaLead();
    $role = Role::create(['name' => 'HS test role', 'permission_type' => 'custom', 'permissions' => $permissions]);
    $user = User::create([
        'name' => 'Synthetic HS Restricted', 'email' => 'hs-restricted@example.invalid',
        'password' => bcrypt('synthetic-password'), 'status' => 1, 'role_id' => $role->id, 'view_permission' => 'individual',
    ]);
    $this->actingAs($user)->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertUnauthorized();
    Http::assertNothingSent();
})->with([[['dashboard']], [['leads.healthsherpa']]]);

it('allows an authorized non-admin agent to quote their own client', function () {
    $lead = makeHealthSherpaLead();
    $role = Role::create(['name' => 'HS quoting agent', 'permission_type' => 'custom', 'permissions' => ['leads.healthsherpa']]);
    $user = User::create([
        'name' => 'Synthetic HS Agent', 'email' => 'hs-agent@example.invalid',
        'password' => bcrypt('synthetic-password'), 'status' => 1, 'role_id' => $role->id, 'view_permission' => 'individual',
    ]);
    $lead->update(['user_id' => $user->id]);
    Http::fake(['https://api.one.healthsherpa.com/v1/quotes' => Http::response(['plans' => []], 200)]);
    $this->actingAs($user)->postJson(route('admin.leads.healthsherpa.quotes', $lead->id), healthSherpaQuoteInput($lead))
        ->assertOk();
    Http::assertSentCount(1);
});
