<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Webkul\Activity\Models\Activity;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Exceptions\TcpaConsentRequiredException;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\ChatwootService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Config::set('chatwoot.base_url', 'https://chat.crmhealth.com');
    Config::set('chatwoot.account_id', '10');
    Config::set('chatwoot.api_token', 'valid_tcpa_api_token');
    Config::set('chatwoot.default_inbox_id', 55);
});

function createTcpaTestLead(bool $withConsent = false): Lead
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Standard Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Roberto TCPA Sanchez',
        'emails' => [['value' => 'roberto.tcpa.'.uniqid().'@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055551234', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Roberto Sanchez - Lead Inquiry',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'has_tcpa_consent' => $withConsent,
        'tcpa_consented_at' => $withConsent ? Carbon::now() : null,
        'tcpa_consent_type' => $withConsent ? 'web_form_optin' : null,
        'tcpa_consent_proof' => $withConsent ? 'IP: 198.51.100.42 / Opt-In Checkbox' : null,
        'chatwoot_conversation_id' => 999111,
    ]);
}

it('blocks outbound chatwoot messaging with 422 TCPA violation when lead has no consent', function () {
    $admin = User::first();
    $this->actingAs($admin);

    $lead = createTcpaTestLead(withConsent: false);

    $response = $this->postJson(route('admin.leads.chatwoot.send', $lead->id), [
        'message' => 'Estimado Roberto, le escribimos sobre su cobertura médica ACA.',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'tcpa_violation' => true,
    ]);

    expect($lead->fresh()->hasTcpaConsent())->toBeFalse();
});

it('allows recording express TCPA consent via API endpoint', function () {
    $admin = User::first();
    $this->actingAs($admin);

    $lead = createTcpaTestLead(withConsent: false);

    $response = $this->postJson(route('admin.leads.chatwoot.record_tcpa_consent', $lead->id), [
        'consent_type' => 'inbound_call_verbal',
        'consent_proof' => 'Grabación de llamada entrante #REC-2026-88741 (Aceptó términos)',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'lead' => [
            'has_tcpa_consent' => true,
            'tcpa_consent_type' => 'inbound_call_verbal',
            'tcpa_consent_proof' => 'Grabación de llamada entrante #REC-2026-88741 (Aceptó términos)',
        ],
    ]);

    $freshLead = $lead->fresh();
    expect($freshLead->has_tcpa_consent)->toBeTrue();
    expect($freshLead->hasTcpaConsent())->toBeTrue();
    expect($freshLead->tcpa_consent_type)->toEqual('inbound_call_verbal');
    expect($freshLead->tcpa_consented_at)->not->toBeNull();
});

it('allows sending chatwoot message once TCPA consent is recorded', function () {
    $admin = User::first();
    $this->actingAs($admin);

    $lead = createTcpaTestLead(withConsent: true);

    Http::fake([
        'https://chat.crmhealth.com/api/v1/accounts/10/conversations/999111/messages' => Http::response([
            'id' => 12345,
            'content' => 'Hola Roberto, confirmamos que su subsidio ACA está aprobado.',
            'message_type' => 'outgoing',
        ], 200),
    ]);

    $response = $this->postJson(route('admin.leads.chatwoot.send', $lead->id), [
        'message' => 'Hola Roberto, confirmamos que su subsidio ACA está aprobado.',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
});

it('causes ChatwootService to throw TcpaConsentRequiredException directly on unconsented lead', function () {
    $lead = createTcpaTestLead(withConsent: false);

    $service = app(ChatwootService::class);

    expect(fn () => $service->sendMessage(999111, 'Prueba de mensaje', $lead))
        ->toThrow(TcpaConsentRequiredException::class);

    expect(fn () => $service->createConversation(500, 'Mensaje inicial', 55, $lead))
        ->toThrow(TcpaConsentRequiredException::class);
});
