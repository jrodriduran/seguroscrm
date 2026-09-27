<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\PolicyServiceCase;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestPolicyForCases(): InsurancePolicy
{
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Cases Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Marcos Hernandez',
        'emails' => [['value' => 'marcos.hernandez@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055554321', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Marcos Hernandez - Ambetter',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    return InsurancePolicy::create([
        'policy_number' => 'POL-AMB-998877',
        'portal_token' => Str::random(40),
        'member_id' => 'MBR-77665544',
        'group_number' => 'GRP-AMB-1010',
        'lead_id' => $lead->id,
        'person_id' => $person->id,
        'user_id' => 1,
        'carrier_name' => 'Ambetter Health',
        'plan_name' => 'Ambetter Balanced Care 11',
        'metal_tier' => 'silver',
        'network_type' => 'HMO',
        'gross_premium' => 450.00,
        'aptc_subsidy' => 450.00,
        'net_premium' => 0.00,
        'effective_date' => Carbon::now()->startOfYear(),
        'status' => 'active',
        'members_count' => 1,
    ]);
}

it('allows agent to create a service case with auto-generated ticket number and attachment', function () {
    Storage::fake('public');
    $user = User::first() ?: User::factory()->create();
    $policy = createTestPolicyForCases();

    $fakeFile = UploadedFile::fake()->create('form_1095a_signed.pdf', 200, 'application/pdf');

    $response = test()->actingAs($user)->postJson("/admin/policies/{$policy->id}/service-cases", [
        'category' => 'tax_1095a',
        'priority' => 'high',
        'subject' => 'Solicitud urgente de Forma 1095-A para IRS',
        'description' => 'El cliente requiere la forma 1095-A para completar su declaración de impuestos 2025.',
        'is_shared_with_client' => true,
        'attachment' => $fakeFile,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $case = PolicyServiceCase::where('policy_id', $policy->id)->first();
    expect($case)->not->toBeNull();
    expect($case->ticket_number)->toStartWith('CAS-');
    expect($case->category)->toBe('tax_1095a');
    expect($case->priority)->toBe('high');
    expect($case->status)->toBe('open');
    expect($case->is_shared_with_client)->toBeTrue();
    expect($case->attachment_path)->not->toBeNull();
    Storage::disk('public')->assertExists($case->attachment_path);
});

it('allows agent to list service cases for a specific policy', function () {
    $user = User::first() ?: User::factory()->create();
    $policy = createTestPolicyForCases();

    PolicyServiceCase::create([
        'policy_id' => $policy->id,
        'lead_id' => $policy->lead_id,
        'user_id' => $user->id,
        'category' => 'address_change',
        'priority' => 'normal',
        'status' => 'open',
        'subject' => 'Cambio de domicilio a Kendall',
    ]);

    $response = test()->actingAs($user)->getJson("/admin/policies/{$policy->id}/service-cases");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'cases' => [
                '*' => ['id', 'ticket_number', 'category', 'status', 'subject'],
            ],
        ]);

    expect(count($response->json('cases')))->toBe(1);
    expect($response->json('cases.0.subject'))->toBe('Cambio de domicilio a Kendall');
});

it('allows agent to update case status and sets resolved_at when resolved', function () {
    $user = User::first() ?: User::factory()->create();
    $policy = createTestPolicyForCases();

    $case = PolicyServiceCase::create([
        'policy_id' => $policy->id,
        'lead_id' => $policy->lead_id,
        'user_id' => $user->id,
        'category' => 'claims_billing',
        'priority' => 'urgent',
        'status' => 'open',
        'subject' => 'Reclamo por cobro indebido de copago',
    ]);

    $response = test()->actingAs($user)->putJson("/admin/service-cases/{$case->id}", [
        'status' => 'resolved',
        'notes' => 'Aseguradora emitió crédito a favor del asegurado.',
    ]);

    $response->assertOk();

    $case->refresh();
    expect($case->status)->toBe('resolved');
    expect($case->resolved_at)->not->toBeNull();
});

it('allows insured client to request 1095-A self-service from portal', function () {
    $policy = createTestPolicyForCases();

    $response = test()->postJson("/my-policy/{$policy->portal_token}/request-1095a", [
        'tax_year' => 2025,
        'notes' => 'Por favor enviar copia a mi contador.',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $case = PolicyServiceCase::where('policy_id', $policy->id)
        ->where('category', 'tax_1095a')
        ->first();

    expect($case)->not->toBeNull();
    expect($case->ticket_number)->toStartWith('CAS-');
    expect($case->subject)->toContain('1095-A');
    expect($case->subject)->toContain('2025');
    expect($case->is_shared_with_client)->toBeTrue();
    expect($case->status)->toBe('open');
});

it('allows insured client to download shared document and denies unshared document', function () {
    Storage::fake('public');
    $policy = createTestPolicyForCases();

    $filePath = "service_cases/{$policy->id}/test_doc_1095a.pdf";
    Storage::disk('public')->put($filePath, 'PDF-DUMMY-CONTENT');

    $sharedCase = PolicyServiceCase::create([
        'policy_id' => $policy->id,
        'lead_id' => $policy->lead_id,
        'category' => 'tax_1095a',
        'priority' => 'normal',
        'status' => 'resolved',
        'subject' => 'Forma 1095-A disponible',
        'attachment_path' => $filePath,
        'attachment_name' => 'Forma_1095A_2025.pdf',
        'is_shared_with_client' => true,
    ]);

    $unsharedCase = PolicyServiceCase::create([
        'policy_id' => $policy->id,
        'lead_id' => $policy->lead_id,
        'category' => 'claims_billing',
        'priority' => 'normal',
        'status' => 'open',
        'subject' => 'Nota interna de reclamo',
        'attachment_path' => $filePath,
        'attachment_name' => 'Nota_Interna.pdf',
        'is_shared_with_client' => false,
    ]);

    // Shared case download: Success
    $response = test()->get("/my-policy/{$policy->portal_token}/service-cases/{$sharedCase->id}/download");
    $response->assertOk();

    // Unshared case download: 403 Forbidden
    $forbiddenResponse = test()->get("/my-policy/{$policy->portal_token}/service-cases/{$unsharedCase->id}/download");
    $forbiddenResponse->assertStatus(403);
});
