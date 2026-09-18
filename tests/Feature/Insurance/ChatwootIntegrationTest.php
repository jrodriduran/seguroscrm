<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\ChatwootService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Config::set('chatwoot.base_url', 'https://chat.agencia.com');
    Config::set('chatwoot.account_id', '42');
    Config::set('chatwoot.api_token', 'test_secret_token_123');
    Config::set('chatwoot.default_inbox_id', 99);
});

function createTestLeadForChatwoot(): Lead
{
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Chatwoot Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Daniela Morales',
        'emails' => [['value' => 'daniela.morales@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '7865559988', 'label' => 'mobile']],
    ]);

    return Lead::create([
        'title' => 'Daniela Morales - ACA Inquiry',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
    ]);
}

it('creates contact and conversation in chatwoot when syncing a lead', function () {
    $lead = createTestLeadForChatwoot();

    Http::fake([
        'https://chat.agencia.com/api/v1/accounts/42/contacts/search*' => Http::response(['payload' => []], 200),
        'https://chat.agencia.com/api/v1/accounts/42/contacts' => Http::response(['payload' => ['contact' => ['id' => 777]]], 200),
        'https://chat.agencia.com/api/v1/accounts/42/conversations' => Http::response(['id' => 888, 'inbox_id' => 99], 200),
        'https://chat.agencia.com/api/v1/accounts/42/conversations/888/messages' => Http::response(['payload' => [
            ['id' => 1, 'content' => 'Hola', 'message_type' => 'incoming', 'created_at' => time()],
        ]], 200),
    ]);

    $service = app(ChatwootService::class);
    $contactId = $service->findOrCreateContact($lead->person, $lead);

    expect($contactId)->toEqual(777);
    expect($lead->person->fresh()->chatwoot_contact_id)->toEqual(777);

    $conv = $service->createConversation($contactId, 'Hola desde Krayin CRM');
    expect($conv['id'])->toEqual(888);
});

it('processes incoming webhook message and creates or links CRM lead with activity', function () {
    $webhookPayload = [
        'event' => 'message_created',
        'message_type' => 'incoming',
        'content' => 'Hola, necesito cotizar un plan de salud para mi familia en Florida.',
        'conversation' => [
            'id' => 5001,
            'inbox_id' => 99,
        ],
        'sender' => [
            'id' => 3001,
            'name' => 'Manuel Garcia',
            'phone_number' => '+13055554321',
            'email' => 'manuel.garcia@example.com',
        ],
    ];

    $response = $this->postJson(route('chatwoot.webhook'), $webhookPayload);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'event' => 'message_created',
        ]);

    // Check Person was created/linked
    $person = Person::where('chatwoot_contact_id', 3001)->first();
    expect($person)->not->toBeNull();
    expect($person->name)->toEqual('Manuel Garcia');

    // Check Lead was created and linked to conversation 5001
    $lead = Lead::where('chatwoot_conversation_id', 5001)->first();
    expect($lead)->not->toBeNull();
    expect($lead->chatwoot_conversation_id)->toEqual(5001);
    expect($lead->chatwoot_last_message_at)->not->toBeNull();

    // Check CRM activity was created
    $this->assertDatabaseHas('activities', [
        'lead_id' => $lead->id,
        'title' => '💬 Mensaje WhatsApp/Chatwoot de Manuel Garcia',
    ]);
});

it('allows sending an outbound message from CRM to Chatwoot', function () {
    $lead = createTestLeadForChatwoot();
    $lead->update(['chatwoot_conversation_id' => 8888]);
    $admin = User::first();

    Http::fake([
        'https://chat.agencia.com/api/v1/accounts/42/conversations/8888/messages' => Http::response([
            'id' => 9999,
            'content' => 'Hola Daniela, tu subsidio estimado es de $520/mes.',
            'message_type' => 'outgoing',
        ], 200),
    ]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.leads.chatwoot.send', $lead->id), [
            'message' => 'Hola Daniela, tu subsidio estimado es de $520/mes.',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    // Check outbound activity was created in CRM
    $this->assertDatabaseHas('activities', [
        'lead_id' => $lead->id,
        'title' => '📤 Mensaje Saliente Chatwoot a Daniela Morales',
    ]);
});
