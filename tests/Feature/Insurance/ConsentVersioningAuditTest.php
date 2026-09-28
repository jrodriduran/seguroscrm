<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\LeadConsentVersion;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createConsentAuditLead(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Consent Audit Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Alejandro Morales',
        'emails' => [['value' => 'alejandro@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865559988', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Alejandro Morales - ACA Audit Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('records an immutable version with SHA-256 fingerprint when client signs consent', function () {
    $lead = createConsentAuditLead();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Alejandro Morales',
        'agent_name' => 'Licensed Agent',
        'agent_npn' => '19845210',
        'agency_name' => 'Health CRM Agency',
        'consent_text' => 'CMS 45 CFR 155.220 compliance legal disclosure authorization text.',
    ]);

    $fakeSig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $version = $consent->recordSignature(
        signatureData: $fakeSig,
        ipAddress: '198.51.100.42',
        userAgent: 'Mozilla/5.0 Agent Audit Test Browser'
    );

    // Verify version created
    expect($version)->toBeInstanceOf(LeadConsentVersion::class);
    expect($version->version_number)->toBe(1);
    expect($version->status)->toBe('active');
    expect($version->ip_address)->toBe('198.51.100.42');
    expect(strlen($version->consent_sha256))->toBe(64);
    expect($version->verifyIntegrity())->toBeTrue();

    // Verify parent consent status
    $consent->refresh();
    expect($consent->status)->toBe('signed');
    expect($consent->signature_data)->toBe($fakeSig);
});

it('detects tampering when consent text or signature is altered', function () {
    $lead = createConsentAuditLead();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Alejandro Morales',
        'consent_text' => 'Original text',
    ]);

    $version = $consent->recordSignature(
        signatureData: 'data:image/png;base64,original-signature',
        ipAddress: '127.0.0.1',
        userAgent: 'Browser/1.0'
    );

    expect($version->verifyIntegrity())->toBeTrue();

    // Simulate tampering of text directly in database
    $version->consent_text = 'Tampered text without recomputing SHA-256';
    expect($version->verifyIntegrity())->toBeFalse();
});

it('archives previous signed version as superseded when regenerating consent token for compliance', function () {
    $admin = getDefaultAdmin();
    $lead = createConsentAuditLead();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Alejandro Morales',
        'consent_text' => 'CMS Year 2026 consent agreement',
    ]);

    $firstVersion = $consent->recordSignature(
        signatureData: 'data:image/png;base64,first-year-signature',
        ipAddress: '10.0.0.1',
        userAgent: 'Browser/1.0'
    );

    expect($firstVersion->status)->toBe('active');
    expect($firstVersion->superseded_at)->toBeNull();

    // Regenerate consent (e.g. for annual OEP renewal or scope change)
    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.consent.regenerate', $lead->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    // Check first version is preserved with status superseded
    $firstVersion->refresh();
    expect($firstVersion->status)->toBe('superseded');
    expect($firstVersion->superseded_at)->not->toBeNull();
    expect($firstVersion->verifyIntegrity())->toBeTrue();

    // Check consent is reset to pending with a fresh token
    $consent->refresh();
    expect($consent->status)->toBe('pending');
    expect($consent->signature_data)->toBeNull();
    expect($consent->token)->not->toBeNull();
});

it('preserves signature record when consent is revoked', function () {
    $admin = getDefaultAdmin();
    $lead = createConsentAuditLead();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Alejandro Morales',
        'consent_text' => 'Consent text',
    ]);

    $version = $consent->recordSignature(
        signatureData: 'data:image/png;base64,valid-signature',
        ipAddress: '10.0.0.5',
        userAgent: 'Browser/1.0'
    );

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.consent.revoke', $lead->id), [
            'reason' => 'Client requested cancellation of agent representation',
        ]);

    $response->assertOk();

    $version->refresh();
    expect($version->status)->toBe('revoked');
    expect($version->revocation_reason)->toBe('Client requested cancellation of agent representation');
    // Signature data must be retained for CMS audits
    expect($version->signature_data)->toBe('data:image/png;base64,valid-signature');
});

it('exports 10-year CMS compliance audit JSON with complete version history', function () {
    $admin = getDefaultAdmin();
    $lead = createConsentAuditLead();

    $consent = LeadConsent::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'client_name' => 'Alejandro Morales',
        'consent_text' => 'CMS Disclosure Text',
    ]);

    $consent->recordSignature(
        signatureData: 'data:image/png;base64,audit-sig-data',
        ipAddress: '192.0.2.1',
        userAgent: 'Chrome/120'
    );

    $response = test()->actingAs($admin)
        ->getJson(route('admin.leads.consent.audit_export', $lead->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'retention_standard' => 'CMS 45 CFR 155.220 (10-Year Immutable Audit Trail)',
        ])
        ->assertJsonStructure([
            'success',
            'lead_id',
            'retention_standard',
            'generated_at',
            'versions' => [
                '*' => [
                    'version_number',
                    'status',
                    'signed_at',
                    'ip_address',
                    'consent_sha256',
                    'integrity_verified',
                ],
            ],
        ]);

    $versions = $response->json('versions');
    expect($versions)->toHaveCount(1);
    expect($versions[0]['integrity_verified'])->toBeTrue();
});
