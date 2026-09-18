<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadMedicareSoa;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestMedicareLead(): Lead
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
        'name' => 'Robert Johnson',
        'emails' => [['value' => 'robert.johnson@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055557890', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Robert Johnson - Medicare Advantage 2026',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('allows beneficiary to view Medicare SOA signing portal with CMS TPMO disclaimer', function () {
    $lead = createTestMedicareLead();

    $soa = LeadMedicareSoa::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'beneficiary_name' => 'Robert Johnson',
        'beneficiary_phone' => '3055557890',
        'agent_name' => 'Carlos Hernandez',
        'agent_npn' => '18992014',
        'agency_name' => 'Prime Health & Senior Solutions',
    ]);

    test()->get(route('medicare.soa.portal', $soa->token))
        ->assertOk()
        ->assertSee('Scope of Appointment (SOA)')
        ->assertSee('Robert Johnson')
        ->assertSee('Carlos Hernandez')
        ->assertSee('18992014')
        ->assertSee('No ofrecemos todos los planes disponibles en su área');
});

it('signs Medicare SOA and enforces the CMS 48-Hour waiting period rule', function () {
    $lead = createTestMedicareLead();

    $soa = LeadMedicareSoa::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'pending',
        'beneficiary_name' => 'Robert Johnson',
        'agent_name' => 'Carlos Hernandez',
        'agent_npn' => '18992014',
    ]);

    $fakeSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $response = test()->postJson(route('medicare.soa.sign', $soa->token), [
        'beneficiary_name' => 'Robert Johnson Sr.',
        'beneficiary_phone' => '3055557890',
        'beneficiary_address' => '742 Evergreen Terrace, Miami, FL 33101',
        'medicare_number' => '1EG4-TE5-MK72',
        'signature_data' => $fakeSignature,
        'discuss_medicare_advantage' => 1,
        'discuss_prescription_drug' => 1,
        'discuss_medigap' => 0,
        'discuss_dental_vision' => 1,
        'discuss_hospital_indemnity' => 0,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $soa->refresh();
    expect($soa->status)->toBe('signed');
    expect($soa->signed_at)->not->toBeNull();
    expect($soa->appointment_eligible_at)->not->toBeNull();
    expect($soa->discuss_medicare_advantage)->toBeTrue();
    expect($soa->discuss_prescription_drug)->toBeTrue();
    expect($soa->discuss_dental_vision)->toBeTrue();
    expect($soa->discuss_medigap)->toBeFalse();

    // 48-Hour rule check: right after signing, not yet eligible unless 48 hours pass
    expect($soa->is_eligible_for_appointment)->toBeFalse();
    expect($soa->hours_remaining_until_eligible)->toBeGreaterThanOrEqual(47);
});

it('waives the 48-hour wait when a valid CMS exception is documented', function () {
    $lead = createTestMedicareLead();

    $soa = LeadMedicareSoa::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => 1,
        'token' => Str::random(36),
        'status' => 'signed',
        'beneficiary_name' => 'Robert Johnson',
        'agent_name' => 'Carlos Hernandez',
        'agent_npn' => '18992014',
        'signed_at' => Carbon::now(),
        'appointment_eligible_at' => Carbon::now()->addHours(48),
        'exception_reason' => 'none',
        'signature_data' => 'data:image/png;base64,sample',
    ]);

    expect($soa->is_eligible_for_appointment)->toBeFalse();

    $admin = getDefaultAdmin();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.soa.exception', $lead->id), [
            'exception_reason' => 'walk_in',
            'notes' => 'El beneficiario acudió directamente a la oficina de la agencia solicitando orientación inmediata.',
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $soa->refresh();
    expect($soa->exception_reason)->toBe('walk_in');
    expect($soa->is_eligible_for_appointment)->toBeTrue();
    expect($soa->hours_remaining_until_eligible)->toBe(0);
});

it('generates the official CMS audit certificate HTML and PDF', function () {
    $admin = getDefaultAdmin();
    $lead = createTestMedicareLead();

    $soa = LeadMedicareSoa::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(36),
        'status' => 'signed',
        'beneficiary_name' => 'Robert Johnson',
        'beneficiary_phone' => '3055557890',
        'beneficiary_address' => 'Miami, FL',
        'agent_name' => 'Carlos Hernandez',
        'agent_npn' => '18992014',
        'agency_name' => 'Prime Health & Senior Solutions',
        'discuss_medicare_advantage' => true,
        'discuss_prescription_drug' => true,
        'signed_at' => Carbon::now()->subHours(50),
        'appointment_eligible_at' => Carbon::now()->subHours(2),
        'signature_data' => 'data:image/png;base64,sample',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 Test Suite',
    ]);

    test()->actingAs($admin)
        ->get(route('admin.leads.soa.certificate', $lead->id))
        ->assertOk()
        ->assertSee('CMS SCOPE OF APPOINTMENT (SOA) COMPLIANCE CERTIFICATE')
        ->assertSee('Robert Johnson')
        ->assertSee('18992014')
        ->assertSee('127.0.0.1');

    test()->actingAs($admin)
        ->get(route('admin.leads.soa.certificate.pdf', $lead->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
