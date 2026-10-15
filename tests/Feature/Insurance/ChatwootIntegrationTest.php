<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Webkul\Activity\Models\Activity;
use Webkul\Communications\Models\CommunicationMessage;
use Webkul\Communications\Services\CommunicationSettings;
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
    app(CommunicationSettings::class)->set('chatwoot.webhook_token', 'test-webhook-token');
    Http::preventStrayRequests();
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
        'has_tcpa_consent' => true,
        'tcpa_consented_at' => now(),
        'tcpa_consent_type' => 'digital_form',
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

it('processes incoming webhook message once and links it to the contact and lead timeline', function () {
    $webhookPayload = [
        'id' => 7001,
        'account' => ['id' => 42],
        'event' => 'message_created',
        'message_type' => 'incoming',
        'content' => 'Hola, necesito cotizar un plan de salud para mi familia en Florida.',
        'conversation' => [
            'id' => 5001,
            'inbox_id' => 99,
            'channel' => 'Channel::Whatsapp',
        ],
        'sender' => [
            'id' => 3001,
            'name' => 'Manuel Garcia',
            'phone_number' => '+13055554321',
            'email' => 'manuel.garcia@example.com',
        ],
    ];

    $webhookUrl = route('communications.chatwoot.webhook', ['token' => 'test-webhook-token']);
    $response = $this->postJson($webhookUrl, $webhookPayload);

    $response->assertOk()
        ->assertJson([
            'status' => 'logged',
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

    // Communications owns the incoming message timeline now, rather than Activity.
    $this->assertDatabaseHas('communication_messages', [
        'provider' => 'chatwoot',
        'external_id' => '7001',
        'conversation_id' => '5001',
        'person_id' => $person->id,
        'lead_id' => $lead->id,
        'channel' => 'whatsapp',
        'direction' => 'in',
        'content' => $webhookPayload['content'],
    ]);

    $this->postJson($webhookUrl, $webhookPayload)
        ->assertOk()
        ->assertJsonPath('status', 'duplicate');

    expect(CommunicationMessage::where('provider', 'chatwoot')->where('external_id', '7001')->count())->toBe(1);
    expect(Person::where('chatwoot_contact_id', 3001)->count())->toBe(1);
    expect(Lead::where('chatwoot_conversation_id', 5001)->count())->toBe(1);
    Http::assertNothingSent();
});

it('rejects chatwoot webhook requests with missing or invalid tokens before storing messages', function () {
    $payload = ['id' => 7002, 'event' => 'message_created', 'account' => ['id' => 42]];

    foreach ([[], ['token' => 'incorrect-token']] as $query) {
        $this->postJson(route('communications.chatwoot.webhook', $query), $payload)
            ->assertUnauthorized();
    }

    $this->assertDatabaseMissing('communication_messages', ['external_id' => '7002']);
    Http::assertNothingSent();
});

it('ignores chatwoot events belonging to another account before storing messages', function () {
    $this->postJson(route('communications.chatwoot.webhook', ['token' => 'test-webhook-token']), [
        'id' => 7003,
        'event' => 'message_created',
        'account' => ['id' => 43],
    ])->assertOk()->assertJson(['status' => 'ignored', 'reason' => 'account']);

    $this->assertDatabaseMissing('communication_messages', ['external_id' => '7003']);
    Http::assertNothingSent();
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
        'title' => '📤 Mensaje Saliente Chatwoot a Daniela Morales',
    ]);

    $outActivity = Activity::where('title', '📤 Mensaje Saliente Chatwoot a Daniela Morales')->first();
    $this->assertDatabaseHas('lead_activities', [
        'lead_id' => $lead->id,
        'activity_id' => $outActivity->id,
    ]);
});
