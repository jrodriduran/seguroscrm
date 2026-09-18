<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestLeadWithPerson(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Test Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Maria Gonzalez',
        'emails' => [['value' => 'maria@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865551234', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Maria Gonzalez - ACA Health',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('allows customer to view the consent signing portal with valid token', function () {
    $lead = createTestLeadWithPerson();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Maria Gonzalez',
        'client_phone' => '7865551234',
        'client_email' => 'maria@example.com',
        'agent_name' => 'John Agent',
        'agent_npn' => '19845210',
        'agency_name' => 'Seguros CRM Agency',
        'consent_text' => 'Autorizo al agente a representarme en el Mercado de Seguros.',
    ]);

    test()->get(route('consent.portal', $consent->token))
        ->assertOk()
        ->assertSee('Autorización y Consentimiento CMS')
        ->assertSee('Maria Gonzalez')
        ->assertSee('John Agent')
        ->assertSee('19845210');
});

it('returns 404 for invalid consent portal token', function () {
    test()->get(route('consent.portal', 'invalid-token-12345'))
        ->assertNotFound();
});

it('records client electronic signature and audit information', function () {
    $lead = createTestLeadWithPerson();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Maria Gonzalez',
        'agent_name' => 'John Agent',
        'agent_npn' => '19845210',
        'agency_name' => 'Seguros CRM Agency',
        'consent_text' => 'Texto legal de autorización CMS.',
    ]);

    $fakeSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $response = test()->postJson(route('consent.sign', $consent->token), [
        'signature' => $fakeSignature,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $consent->refresh();
    expect($consent->status)->toBe('signed');
    expect($consent->signature_data)->toBe($fakeSignature);
    expect($consent->signed_at)->not->toBeNull();
});

it('allows admin to view and download the compliance certificate', function () {
    $admin = getDefaultAdmin();
    $lead = createTestLeadWithPerson();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(36),
        'status' => 'signed',
        'client_name' => 'Maria Gonzalez',
        'client_phone' => '7865551234',
        'agent_name' => 'Admin Agent',
        'agent_npn' => '19845210',
        'agency_name' => 'Seguros CRM Agency',
        'consent_text' => 'Texto legal CMS.',
        'signed_at' => now(),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 Test Browser',
    ]);

    test()->actingAs($admin)
        ->get(route('admin.leads.consent.certificate', $lead->id))
        ->assertOk()
        ->assertSee('CERTIFICADO DE AUTORIZACIÓN Y CONSENTIMIENTO CMS')
        ->assertSee('Maria Gonzalez')
        ->assertSee('192.168.1.100');

    test()->actingAs($admin)
        ->get(route('admin.leads.consent.certificate.pdf', $lead->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
