<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadSepQualification;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

uses(DatabaseTransactions::class);

function createTestEnrollmentLead(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Enrollment Test Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'New',
        'code' => 'new',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Ana Ramirez',
        'emails' => [['value' => 'ana.ramirez@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '4075554321', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Ana Ramirez - SEP Enrollment',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('returns federal enrollment status and event definitions for a lead', function () {
    $admin = getDefaultAdmin();
    $lead = createTestEnrollmentLead();

    $response = test()->actingAs($admin)
        ->getJson(route('admin.leads.enrollment.get', $lead->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'lead_id' => $lead->id,
        ])
        ->assertJsonStructure([
            'federal_status' => ['is_oep', 'is_sep', 'period_name', 'description'],
            'event_definitions' => [
                'loss_of_coverage',
                'marriage',
                'birth_adoption',
                'permanent_move',
                'immigration_status',
                'income_change',
            ],
        ]);
});

it('validates a qualifying life event within the 60-day window and generates checklist', function () {
    $admin = getDefaultAdmin();
    $lead = createTestEnrollmentLead();

    // Event occurred 15 days ago
    $eventDate = Carbon::today()->subDays(15)->toDateString();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.enrollment.save', $lead->id), [
            'event_type' => 'loss_of_coverage',
            'event_date' => $eventDate,
            'notes' => 'El cliente fue despedido de su trabajo y perdió su plan de salud grupal.',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $qualification = LeadSepQualification::where('lead_id', $lead->id)->first();
    expect($qualification)->not->toBeNull();
    expect($qualification->is_eligible)->toBeTrue();
    expect($qualification->is_expired)->toBeFalse();
    expect($qualification->days_remaining)->toBe(45); // 60 - 15 = 45 days
    expect($qualification->required_documents)->toBeArray();
    expect(count($qualification->required_documents))->toBeGreaterThan(0);
});

it('detects when an event occurred outside the legal 60-day window', function () {
    $admin = getDefaultAdmin();
    $lead = createTestEnrollmentLead();

    // Event occurred 70 days ago (window closed)
    $expiredEventDate = Carbon::today()->subDays(70)->toDateString();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.enrollment.save', $lead->id), [
            'event_type' => 'permanent_move',
            'event_date' => $expiredEventDate,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $qualification = LeadSepQualification::where('lead_id', $lead->id)->first();
    expect($qualification->is_eligible)->toBeFalse();
    expect($qualification->is_expired)->toBeTrue();
    expect($qualification->days_remaining)->toBe(0);
});

it('toggles CMS document verification checklist items', function () {
    $admin = getDefaultAdmin();
    $lead = createTestEnrollmentLead();

    $qualification = LeadSepQualification::create([
        'lead_id' => $lead->id,
        'user_id' => $admin?->id ?: 1,
        'event_type' => 'marriage',
        'event_date' => Carbon::today()->subDays(5)->toDateString(),
        'sep_deadline' => Carbon::today()->addDays(55)->toDateString(),
        'is_eligible' => true,
        'required_documents' => ['Certificado de Matrimonio oficial registrado'],
        'verified_documents' => [],
    ]);

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.enrollment.toggle_document', $lead->id), [
            'document' => 'Certificado de Matrimonio oficial registrado',
            'verified' => true,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'all_verified' => true,
        ]);

    $qualification->refresh();
    expect($qualification->verified_documents)->toContain('Certificado de Matrimonio oficial registrado');
    expect($qualification->verified_at)->not->toBeNull();
});
