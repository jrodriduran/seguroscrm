<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Quote\Models\HealthPlanProposal;
use Webkul\Quote\Models\Quote;

uses(DatabaseTransactions::class);

function createTestLeadWithTwoQuotes(): array
{
    $pipeline = Pipeline::first() ?: Pipeline::create([
        'name' => 'Health Insurance Pipeline',
        'is_default' => 1,
    ]);

    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create([
        'name' => 'Quoted',
        'code' => 'quoted',
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $person = Person::create([
        'name' => 'Carlos Delgado',
        'emails' => [['value' => 'carlos.delgado@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865559876', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Carlos Delgado - ACA Health Comparison',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => 1,
    ]);

    $quote1 = Quote::create([
        'subject' => 'Florida Blue - BlueOptions Silver 1410',
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'myBlue Silver 1410',
        'metal_tier' => 'silver',
        'network_type' => 'EPO',
        'gross_premium' => 520.00,
        'aptc_subsidy' => 495.00,
        'net_premium' => 25.00,
        'deductible' => 750.00,
        'out_of_pocket_max' => 2900.00,
        'copay_primary_care' => 10.00,
        'copay_specialist' => 35.00,
        'copay_generic_drugs' => 5.00,
        'sub_total' => 520.00,
        'discount_amount' => 495.00,
        'grand_total' => 25.00,
        'user_id' => 1,
        'person_id' => $person->id,
    ]);

    $quote2 = Quote::create([
        'subject' => 'Oscar - Simple Bronze 2026',
        'carrier_name' => 'Oscar Health',
        'plan_name' => 'Classic Bronze Saver',
        'metal_tier' => 'bronze',
        'network_type' => 'EPO',
        'gross_premium' => 450.00,
        'aptc_subsidy' => 450.00,
        'net_premium' => 0.00,
        'deductible' => 6500.00,
        'out_of_pocket_max' => 9100.00,
        'copay_primary_care' => 30.00,
        'copay_specialist' => 75.00,
        'copay_generic_drugs' => 15.00,
        'sub_total' => 450.00,
        'discount_amount' => 450.00,
        'grand_total' => 0.00,
        'user_id' => 1,
        'person_id' => $person->id,
    ]);

    $lead->quotes()->attach([$quote1->id, $quote2->id]);

    return [$lead, $quote1, $quote2];
}

it('generates a side-by-side health plan comparison proposal for a lead', function () {
    $admin = getDefaultAdmin();
    [$lead, $quote1, $quote2] = createTestLeadWithTwoQuotes();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.quotes.proposals.generate', $lead->id), [
            'quote_ids' => [$quote1->id, $quote2->id],
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'proposal' => ['id', 'token', 'title'],
            'quotes',
            'public_url',
            'pdf_url',
            'whatsapp_url',
        ]);

    $proposal = HealthPlanProposal::where('lead_id', $lead->id)->first();
    expect($proposal)->not->toBeNull();
    expect($proposal->quote_ids)->toContain($quote1->id, $quote2->id);
    expect($proposal->whatsapp_message)->toContain('Florida Blue');
    expect($proposal->whatsapp_message)->toContain('Oscar Health');
});

it('renders and downloads the side-by-side comparison proposal PDF', function () {
    $admin = getDefaultAdmin();
    [$lead, $quote1, $quote2] = createTestLeadWithTwoQuotes();

    $proposal = HealthPlanProposal::create([
        'lead_id' => $lead->id,
        'user_id' => $admin?->id ?: 1,
        'token' => Str::random(40),
        'title' => 'Comparativa de Planes de Salud ACA',
        'quote_ids' => [$quote1->id, $quote2->id],
        'status' => 'sent',
    ]);

    test()->actingAs($admin)
        ->get(route('admin.quotes.proposals.print', $proposal->id))
        ->assertOk()
        ->assertSee('Propuesta Comparativa de Salud')
        ->assertSee('Florida Blue')
        ->assertSee('Oscar Health');

    test()->actingAs($admin)
        ->get(route('admin.quotes.proposals.download_pdf', $proposal->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('allows client to view public comparison portal and choose their favorite plan', function () {
    [$lead, $quote1, $quote2] = createTestLeadWithTwoQuotes();

    $proposal = HealthPlanProposal::create([
        'lead_id' => $lead->id,
        'user_id' => 1,
        'token' => Str::random(40),
        'title' => 'Comparativa de Planes de Salud ACA',
        'quote_ids' => [$quote1->id, $quote2->id],
        'status' => 'sent',
    ]);

    test()->get(route('proposal.portal.show', $proposal->token))
        ->assertOk()
        ->assertSee('Carlos Delgado')
        ->assertSee('myBlue Silver 1410')
        ->assertSee('Classic Bronze Saver');

    $proposal->refresh();
    expect($proposal->status)->toBe('viewed');
    expect($proposal->viewed_at)->not->toBeNull();

    // Client selects Quote 1 (Florida Blue)
    $response = test()->postJson(route('proposal.portal.select', $proposal->token), [
        'quote_id' => $quote1->id,
        'notes' => 'Me gusta este plan por el copago bajo de $10 en médico primario.',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $proposal->refresh();
    expect($proposal->status)->toBe('accepted');
    expect($proposal->selected_quote_id)->toBe($quote1->id);
    expect($proposal->accepted_at)->not->toBeNull();
    expect($proposal->client_notes)->toContain('copago bajo');

    $quote1->refresh();
    expect($quote1->quote_status)->toBe('accepted');

    test()->get(route('proposal.portal.thankyou', $proposal->token))
        ->assertOk()
        ->assertSee('¡Felicitaciones!')
        ->assertSee('myBlue Silver 1410');
});
