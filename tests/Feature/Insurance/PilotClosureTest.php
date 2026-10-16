<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\LeadMedicareSoa;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function closureScenario(): array
{
    $admin = getDefaultAdmin();
    DB::table('insurance_agent_identities')->where('user_id', $admin->id)->delete();
    $person = Person::create(['name' => 'Synthetic Pilot Client']);
    $pipeline = Pipeline::firstOrFail();
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->firstOrFail();
    $lead = Lead::create([
        'title' => 'Synthetic Pilot', 'person_id' => $person->id, 'user_id' => $admin->id,
        'lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $stage->id,
    ]);

    return compact('admin', 'lead');
}

it('requires an explicit assigned agent identity instead of inventing credentials', function ($kind) {
    extract(closureScenario());
    $this->actingAs($admin)->getJson(route('admin.leads.'.$kind.'.get', $lead->id))
        ->assertUnprocessable()->assertJsonValidationErrors('agent_identity');
    expect(LeadConsent::where('lead_id', $lead->id)->count())->toBe(0);
    expect(LeadMedicareSoa::where('lead_id', $lead->id)->count())->toBe(0);
})->with(['consent', 'soa']);

it('uses configured identity consistently and keeps it distinct from license verification', function ($kind) {
    extract(closureScenario());
    $this->artisan('insurance:agent-identity', ['email' => $admin->email, 'npn' => '900000001', 'agency' => 'Synthetic Legal Agency'])->assertSuccessful();
    $this->actingAs($admin)->getJson(route('admin.leads.'.$kind.'.get', $lead->id))
        ->assertOk()->assertJsonPath($kind.'.agent_npn', '900000001')
        ->assertJsonPath($kind.'.agent_name', $admin->name)->assertJsonPath($kind.'.agency_name', 'Synthetic Legal Agency');
})->with(['consent', 'soa']);

it('denies another agents sensitive documents and medical records before reading or mutating them', function ($route, $method) {
    extract(closureScenario());
    $other = User::create(['name' => 'Synthetic Restricted Agent', 'email' => 'pilot-agent@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $admin->role_id, 'view_permission' => 'individual']);
    $this->actingAs($other)->{$method}(route($route, $lead->id))->assertUnauthorized();
})->with([
    ['admin.leads.consent.get', 'getJson'], ['admin.leads.consent.audit_export', 'getJson'],
    ['admin.leads.consent.regenerate', 'postJson'], ['admin.leads.consent.revoke', 'postJson'],
    ['admin.leads.soa.get', 'getJson'], ['admin.leads.soa.regenerate', 'postJson'],
    ['admin.leads.soa.exception', 'postJson'], ['admin.insurance.leads.rx_network.summary', 'getJson'],
    ['admin.insurance.leads.rx_network.pdf', 'getJson'], ['admin.insurance.leads.rx_medications.store', 'postJson'],
    ['admin.insurance.leads.doctor_networks.store', 'postJson'], ['admin.leads.cross_sell.index', 'getJson'],
    ['admin.leads.cross_sell.evaluate', 'postJson'],
]);

it('does not fabricate carrier participation when registering a doctor', function () {
    extract(closureScenario());
    $this->actingAs($admin)->postJson(route('admin.insurance.leads.doctor_networks.store', $lead->id), ['doctor_name' => 'Synthetic Doctor'])
        ->assertOk()->assertJsonPath('doctor.carrier_network_status', []);
});

it('rejects invalid carrier status values before they can break the medical panel', function () {
    extract(closureScenario());
    $this->actingAs($admin)->postJson(route('admin.insurance.leads.doctor_networks.store', $lead->id), [
        'doctor_name' => 'Synthetic Doctor', 'carrier_network_status' => ['Synthetic Carrier' => ['unexpected' => true]],
    ])->assertUnprocessable()->assertJsonValidationErrors('carrier_network_status.Synthetic Carrier');
});

it('exposes a working explicit cross sell evaluation action', function () {
    extract(closureScenario());
    $this->actingAs($admin)->postJson(route('admin.leads.cross_sell.evaluate', $lead->id))->assertOk()->assertJsonPath('success', true);
});

it('renders medical and cross sell endpoints from the registered routes', function () {
    extract(closureScenario());
    $this->actingAs($admin);
    foreach (['rx_and_doctors', 'cross_sell_matrix'] as $partial) {
        $html = Blade::render("@include('admin::leads.view.".$partial."') @stack('scripts')", compact('lead'));
        $html = str_replace('\\/', '/', $html);
        expect($html)->not->toContain('/admin/insurance/leads/')->toContain('/leads/'.$lead->id.'/');
    }
});

it('preserves a signed SOA on repeated public signing and regeneration attempts', function () {
    extract(closureScenario());
    $soa = LeadMedicareSoa::create([
        'lead_id' => $lead->id, 'person_id' => $lead->person_id, 'user_id' => $admin->id,
        'token' => Str::random(36), 'status' => 'signed', 'beneficiary_name' => 'Synthetic Client',
        'signature_data' => 'original-signature', 'signed_at' => now(),
    ]);
    $this->postJson(route('medicare.soa.sign', $soa->token), ['signature' => 'replacement'])->assertUnprocessable();
    $this->actingAs($admin)->postJson(route('admin.leads.soa.regenerate', $lead->id))->assertUnprocessable();
    expect($soa->fresh()->signature_data)->toBe('original-signature');
});

it('does not reactivate a revoked consent through its old public link', function () {
    extract(closureScenario());
    $consent = LeadConsent::create([
        'lead_id' => $lead->id, 'person_id' => $lead->person_id, 'user_id' => $admin->id,
        'token' => Str::random(36), 'status' => 'revoked', 'client_name' => 'Synthetic Client',
        'consent_text' => 'Synthetic consent',
    ]);
    $this->postJson(route('consent.sign', $consent->token), ['signature' => 'data:image/png;base64,replacement'])->assertUnprocessable();
    expect($consent->fresh()->status)->toBe('revoked');
    expect($consent->versions()->count())->toBe(0);
});
