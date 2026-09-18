<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestLeadForDmi(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Insurance Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Enrollment',
        'code' => 'enrollment',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Carlos Rodriguez',
        'contact_numbers' => [['value' => '3055557890', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Carlos Rodriguez - ACA Plan',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('allows admin to create a DMI document requirement for a lead', function () {
    $admin = getDefaultAdmin();
    $lead = createTestLeadForDmi();

    $deadline = Carbon::today()->addDays(90)->format('Y-m-d');

    $response = test()->actingAs($admin)->postJson(route('admin.leads.dmi.store', $lead->id), [
        'doc_type' => 'income',
        'title' => 'Taxes 2025 W-2 Form',
        'deadline_date' => $deadline,
        'notes' => 'El cliente debe enviar la forma W-2 del último empleador.',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $doc = LeadDmiDocument::where('lead_id', $lead->id)->first();
    expect($doc)->not->toBeNull();
    expect($doc->doc_type)->toBe('income');
    expect($doc->title)->toBe('Taxes 2025 W-2 Form');
    expect($doc->days_remaining)->toBe(90);
    expect($doc->urgency_level)->toBe('normal');
});

it('calculates critical urgency when DMI document has 15 days or less remaining', function () {
    $lead = createTestLeadForDmi();

    $doc = LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'doc_type' => 'immigration',
        'title' => 'Permiso de Trabajo I-766',
        'deadline_date' => Carbon::today()->addDays(10),
        'status' => 'pending_upload',
    ]);

    expect($doc->days_remaining)->toBe(10);
    expect($doc->urgency_level)->toBe('critical');
    expect($doc->whatsapp_reminder_message)->toContain('URGENTE: RIESGO DE PÉRDIDA DE SUBSIDIO MÉDICO');
});

it('allows admin to update DMI document status and verify it', function () {
    $admin = getDefaultAdmin();
    $lead = createTestLeadForDmi();

    $doc = LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'doc_type' => 'income',
        'deadline_date' => Carbon::today()->addDays(45),
        'status' => 'pending_upload',
    ]);

    $response = test()->actingAs($admin)->putJson(route('admin.leads.dmi.update', [$lead->id, $doc->id]), [
        'status' => 'verified_by_cms',
        'notes' => 'Aprobado satisfactoriamente por CMS en Healthcare.gov',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $doc->refresh();
    expect($doc->status)->toBe('verified_by_cms');
    expect($doc->urgency_level)->toBe('verified');
});

it('provides pre-formatted WhatsApp reminder payload for DMI', function () {
    $admin = getDefaultAdmin();
    $lead = createTestLeadForDmi();

    $doc = LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'person_id' => $lead->person_id,
        'doc_type' => 'ssn',
        'title' => 'Tarjeta de Seguro Social',
        'deadline_date' => Carbon::today()->addDays(5),
        'status' => 'pending_upload',
    ]);

    $response = test()->actingAs($admin)->getJson(route('admin.leads.dmi.whatsapp', [$lead->id, $doc->id]));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $data = $response->json();
    expect($data['phone'])->toBe('3055557890');
    expect($data['whatsapp_url'])->toContain('api.whatsapp.com');
    expect($data['message'])->toContain('Tarjeta de Seguro Social');
});
