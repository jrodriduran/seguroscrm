<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDoctorNetwork;
use Webkul\Lead\Models\LeadRxMedication;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createTestLeadForRx(): Lead
{
    $user = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Test Rx Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Guillermo Fernandez',
        'emails' => [['value' => 'guillermo@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055558877', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Guillermo Fernandez - Rx Intake',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $user ? $user->id : 1,
    ]);
}

it('retrieves empty summary when no medications or doctors are linked', function () {
    $lead = createTestLeadForRx();
    $admin = User::first();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.insurance.leads.rx_network.summary', $lead->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'metrics' => [
                'total_drugs' => 0,
                'total_doctors' => 0,
                'total_copay_30d' => 0,
                'has_pcp' => false,
            ],
        ]);
});

it('can store prescription medication with tier classification and utilization management restrictions', function () {
    $lead = createTestLeadForRx();
    $admin = User::first();

    $payload = [
        'medication_name' => 'Eliquis',
        'dosage' => '5 mg',
        'frequency' => 'Twice daily',
        'quantity_per_30_days' => 60,
        'drug_tier' => 'Tier 3: Preferred Brand',
        'requires_prior_authorization' => true,
        'requires_step_therapy' => false,
        'has_quantity_limit' => true,
        'estimated_copay_30d' => 45.00,
        'estimated_copay_90d_mail' => 90.00,
        'notes' => 'Anticoagulant therapy post-cardiac consultation.',
    ];

    $response = $this->actingAs($admin)
        ->postJson(route('admin.insurance.leads.rx_medications.store', $lead->id), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'medication' => [
                'medication_name' => 'Eliquis',
                'drug_tier' => 'Tier 3: Preferred Brand',
                'requires_prior_authorization' => true,
                'estimated_copay_30d' => 45.00,
            ],
        ]);

    $this->assertDatabaseHas('lead_rx_medications', [
        'lead_id' => $lead->id,
        'medication_name' => 'Eliquis',
        'requires_prior_authorization' => true,
    ]);
});

it('can store healthcare provider, assigns PCP, and tracks carrier network statuses', function () {
    $lead = createTestLeadForRx();
    $admin = User::first();

    $payload = [
        'doctor_name' => 'Dr. Fernando Ramirez, MD',
        'specialty' => 'Cardiology',
        'npi_number' => '1928374650',
        'clinic_or_hospital' => 'Baptist Hospital of Miami',
        'address_city_state' => 'Miami, FL 33176',
        'phone' => '305-595-1234',
        'carrier_network_status' => [
            'Florida Blue' => 'In-Network',
            'Ambetter' => 'In-Network',
            'Oscar' => 'Out-of-Network',
        ],
        'is_primary_physician' => true,
        'notes' => 'Attending cardiologist',
    ];

    $response = $this->actingAs($admin)
        ->postJson(route('admin.insurance.leads.doctor_networks.store', $lead->id), $payload);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'doctor' => [
                'doctor_name' => 'Dr. Fernando Ramirez, MD',
                'specialty' => 'Cardiology',
                'is_primary_physician' => true,
            ],
        ]);

    $this->assertDatabaseHas('lead_doctor_networks', [
        'lead_id' => $lead->id,
        'doctor_name' => 'Dr. Fernando Ramirez, MD',
        'is_primary_physician' => true,
    ]);

    // Check summary calculation reflects PCP and metrics
    $summaryRes = $this->actingAs($admin)
        ->getJson(route('admin.insurance.leads.rx_network.summary', $lead->id));

    $summaryRes->assertOk()
        ->assertJson([
            'metrics' => [
                'total_doctors' => 1,
                'has_pcp' => true,
                'pcp_name' => 'Dr. Fernando Ramirez, MD',
            ],
        ]);
});

it('can delete a medication and doctor from the lead profile', function () {
    $lead = createTestLeadForRx();
    $admin = User::first();

    $med = LeadRxMedication::create([
        'lead_id' => $lead->id,
        'medication_name' => 'Lisinopril',
        'dosage' => '10mg',
        'drug_tier' => 'Tier 1: Preferred Generic',
    ]);

    $doc = LeadDoctorNetwork::create([
        'lead_id' => $lead->id,
        'doctor_name' => 'Dr. Maria Lopez',
        'specialty' => 'General Practice',
    ]);

    $delMedRes = $this->actingAs($admin)
        ->deleteJson(route('admin.insurance.leads.rx_medications.delete', [$lead->id, $med->id]));
    $delMedRes->assertOk();
    $this->assertDatabaseMissing('lead_rx_medications', ['id' => $med->id]);

    $delDocRes = $this->actingAs($admin)
        ->deleteJson(route('admin.insurance.leads.doctor_networks.delete', [$lead->id, $doc->id]));
    $delDocRes->assertOk();
    $this->assertDatabaseMissing('lead_doctor_networks', ['id' => $doc->id]);
});

it('downloads prescription and provider network summary as PDF', function () {
    $lead = createTestLeadForRx();
    $admin = User::first();

    LeadRxMedication::create([
        'lead_id' => $lead->id,
        'medication_name' => 'Metformin',
        'dosage' => '500mg',
        'drug_tier' => 'Tier 1: Preferred Generic',
        'estimated_copay_30d' => 5.00,
    ]);

    LeadDoctorNetwork::create([
        'lead_id' => $lead->id,
        'doctor_name' => 'Dr. Ana Gomez',
        'specialty' => 'Internal Medicine',
        'is_primary_physician' => true,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.insurance.leads.rx_network.pdf', $lead->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
