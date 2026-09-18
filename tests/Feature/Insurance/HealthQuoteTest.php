<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Quote\Models\Quote;

uses(DatabaseTransactions::class);

function setupLeadForQuote(): Lead
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'ACA Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Quoted',
        'code' => 'quoted',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Alejandro Morales',
        'contact_numbers' => [['value' => '7869998877', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Alejandro Morales - ACA Plan',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);
}

it('generates WhatsApp proposal text and click-to-chat URL for health quote', function () {
    $admin = getDefaultAdmin();
    $lead = setupLeadForQuote();

    $quote = Quote::create([
        'subject' => 'Propuesta Salud ACA - Florida Blue',
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'BlueOptions Silver 1410',
        'metal_tier' => 'Silver',
        'gross_premium' => 550.00,
        'aptc_subsidy' => 540.00,
        'net_premium' => 10.00,
        'deductible' => 750.00,
        'out_of_pocket_max' => 2500.00,
        'copay_pcp' => '$5',
        'copay_specialist' => '$15',
        'copay_generic_rx' => '$3',
        'quote_status' => 'presented',
        'user_id' => $admin?->id ?: 1,
        'person_id' => $lead->person_id,
        'sub_total' => 550.00,
        'discount_amount' => 540.00,
        'grand_total' => 10.00,
    ]);

    $response = test()->actingAs($admin)->getJson(route('admin.quotes.whatsapp', ['id' => $quote->id]));

    $response->assertOk()
        ->assertJson([
            'status' => true,
            'client_name' => 'Alejandro Morales',
            'client_phone' => '7869998877',
        ]);

    $data = $response->json();
    expect($data['message'])->toContain('Florida Blue');
    expect($data['message'])->toContain('BlueOptions Silver 1410');
    expect($data['message'])->toContain('$10.00');
    expect($data['whatsapp_url'])->toContain('7869998877');
});

it('converts health quote to policy, marks quote bound, and advances lead stage', function () {
    $admin = getDefaultAdmin();
    $lead = setupLeadForQuote();

    $quote = Quote::create([
        'subject' => 'Propuesta Salud - Oscar Silver Classic',
        'carrier_name' => 'Oscar Health',
        'plan_name' => 'Silver Simple Classic',
        'metal_tier' => 'Silver',
        'gross_premium' => 600.00,
        'aptc_subsidy' => 600.00,
        'net_premium' => 0.00,
        'quote_status' => 'presented',
        'user_id' => $admin?->id ?: 1,
        'person_id' => $lead->person_id,
        'sub_total' => 600.00,
        'discount_amount' => 600.00,
        'grand_total' => 0.00,
    ]);

    // Link quote to lead
    $quote->leads()->attach($lead->id);

    $response = test()->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', ['id' => $quote->id]));

    $response->assertOk()
        ->assertJson([
            'status' => true,
        ]);

    $quote->refresh();
    expect($quote->quote_status)->toBe('bound');

    $lead->refresh();
    // Lead should have lead_value updated to net_premium
    expect((float) $lead->lead_value)->toBe(0.0);
});
