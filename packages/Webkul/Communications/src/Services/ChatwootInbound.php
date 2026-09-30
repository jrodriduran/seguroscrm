<?php

namespace Webkul\Communications\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Communications\Models\CommunicationMessage;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;

/**
 * Turns Chatwoot webhook events into CRM records:
 *
 * - every client message (in or out) is logged on the client's timeline,
 * - an unknown sender becomes a new contact and lead for the default owner,
 * - "STOP" withdraws consent for that channel and "START" gives it back,
 * - the client's owner is notified of new incoming messages.
 */
class ChatwootInbound
{
    public const STOP_WORDS = ['stop', 'stopall', 'unsubscribe', 'cancel', 'end', 'quit', 'baja', 'alto', 'parar', 'cancelar'];

    public const START_WORDS = ['start', 'unstop', 'yes', 'alta'];

    /**
     * Consent channels a Chatwoot channel maps to.
     */
    protected const CONSENT_CHANNELS = ['whatsapp' => 'whatsapp', 'sms' => 'sms', 'email' => 'email'];

    public function __construct(
        protected ChatwootAccount $account,
        protected ConsentRegistry $consents,
        protected CommunicationSettings $settings,
    ) {}

    public function handle(array $payload): string
    {
        return match ($payload['event'] ?? null) {
            // All or nothing: no half-created contacts if something fails.
            'message_created' => DB::transaction(fn () => $this->messageCreated($payload)),
            'message_updated' => $this->messageUpdated($payload),
            default => 'ignored',
        };
    }

    protected function messageCreated(array $data): string
    {
        $type = $data['message_type'] ?? null;
        $direction = match ($type) {
            'incoming', 0 => 'in',
            'outgoing', 1, 'template', 3 => 'out',
            default => null,
        };

        // Skip activity messages ("conversation resolved"…) and private notes.
        if (! $direction || ! empty($data['private']) || empty($data['id'])) {
            return 'ignored';
        }

        if (CommunicationMessage::where('provider', 'chatwoot')->where('external_id', (string) $data['id'])->exists()) {
            return 'duplicate';
        }

        $conversation = $data['conversation'] ?? [];
        $contact = $conversation['meta']['sender'] ?? ($direction === 'in' ? ($data['sender'] ?? []) : []);
        $inboxId = (int) ($conversation['inbox_id'] ?? $data['inbox']['id'] ?? 0) ?: null;
        $channel = ChatwootAccount::channelFor($conversation['channel'] ?? null) !== 'chat'
            ? ChatwootAccount::channelFor($conversation['channel'] ?? null)
            : ($this->account->channelOfInbox($inboxId) ?? 'chat');

        $person = $this->findPerson($contact, $conversation['id'] ?? null);
        $lead = null;

        if (! $person && $direction === 'in') {
            [$person, $lead] = $this->createContactAndLead($contact, $channel, $conversation);
        }

        $lead ??= $person ? $this->currentLead($person->id, $conversation['id'] ?? null) : null;

        $message = CommunicationMessage::create([
            'provider' => 'chatwoot',
            'external_id' => (string) $data['id'],
            'conversation_id' => isset($conversation['id']) ? (string) $conversation['id'] : null,
            'inbox_id' => $inboxId,
            'channel' => $channel,
            'direction' => $direction,
            'person_id' => $person?->id,
            'lead_id' => $lead?->id,
            'sender_name' => $data['sender']['name'] ?? null,
            'content' => $data['content'] ?? null,
            'attachments' => collect($data['attachments'] ?? [])->map(fn ($file) => [
                'url' => $file['data_url'] ?? null,
                'type' => $file['file_type'] ?? null,
            ])->filter(fn ($file) => $file['url'])->values()->all() ?: null,
            'status' => $data['status'] ?? 'sent',
            'sent_at' => $this->time($data['created_at'] ?? null),
        ]);

        if ($lead && ! empty($conversation['id'])) {
            DB::table('leads')->where('id', $lead->id)->update([
                'chatwoot_conversation_id' => $conversation['id'],
                'chatwoot_inbox_id' => $inboxId,
                'chatwoot_last_message_at' => $message->sent_at,
            ]);
        }

        if ($person && ! empty($contact['id']) && ! $person->chatwoot_contact_id) {
            DB::table('persons')->where('id', $person->id)->update(['chatwoot_contact_id' => $contact['id']]);
        }

        if ($direction === 'in' && $person) {
            $this->applyKeywords($message, $person->id);
            $this->notifyOwner($message, $person, $lead);
        }

        Event::dispatch('communications.message.received', [$message]);

        return 'logged';
    }

    protected function messageUpdated(array $data): string
    {
        if (empty($data['id']) || empty($data['status'])) {
            return 'ignored';
        }

        $updated = CommunicationMessage::where('provider', 'chatwoot')
            ->where('external_id', (string) $data['id'])
            ->update(['status' => Str::limit((string) $data['status'], 15, '')]);

        return $updated ? 'updated' : 'ignored';
    }

    /**
     * Match by Chatwoot contact, then phone, then email.
     */
    protected function findPerson(array $contact, $conversationId): ?Person
    {
        if (! empty($contact['id']) && ($person = Person::where('chatwoot_contact_id', $contact['id'])->first())) {
            return $person;
        }

        if ($conversationId && ($personId = DB::table('leads')->where('chatwoot_conversation_id', $conversationId)->value('person_id'))) {
            return Person::find($personId);
        }

        if ($digits = substr(preg_replace('/\D/', '', (string) ($contact['phone_number'] ?? '')), -10)) {
            // Stored numbers may be formatted ("+1 (305) 555-1234"): narrow by the last digits, compare normalized.
            if (strlen($digits) >= 7 && ($person = Person::where('contact_numbers', 'like', '%'.substr($digits, -4).'%')->get()
                ->first(fn ($p) => collect($p->contact_numbers)->contains(fn ($n) => str_ends_with(preg_replace('/\D/', '', (string) ($n['value'] ?? '')), $digits))))) {
                return $person;
            }
        }

        if ($email = strtolower(trim((string) ($contact['email'] ?? '')))) {
            return Person::where('emails', 'like', '%'.$email.'%')->get()
                ->first(fn ($p) => collect($p->emails)->contains(fn ($e) => strtolower((string) ($e['value'] ?? '')) === $email));
        }

        return null;
    }

    /**
     * The lead this conversation belongs to, else the client's latest open lead.
     */
    protected function currentLead(int $personId, $conversationId): ?Lead
    {
        if ($conversationId && ($lead = Lead::where('person_id', $personId)->where('chatwoot_conversation_id', $conversationId)->first())) {
            return $lead;
        }

        return Lead::where('person_id', $personId)
            ->whereHas('stage', fn ($query) => $query->whereNotIn('code', ['won', 'lost']))
            ->latest('id')
            ->first();
    }

    /**
     * Someone new wrote in: a contact and a lead for the default owner.
     */
    protected function createContactAndLead(array $contact, string $channel, array $conversation): array
    {
        $ownerId = $this->defaultOwnerId();
        $name = trim((string) ($contact['name'] ?? '')) ?: trans('communications::app.chatwoot.unknown-sender');

        $person = Person::create([
            'name' => $name,
            'emails' => ! empty($contact['email']) ? [['value' => $contact['email'], 'label' => 'work']] : [],
            'contact_numbers' => ! empty($contact['phone_number']) ? [['value' => $contact['phone_number'], 'label' => 'mobile']] : [],
            'user_id' => $ownerId,
            'chatwoot_contact_id' => $contact['id'] ?? null,
        ]);

        if (in_array($channel, ['whatsapp', 'sms', 'telegram', 'web', 'messenger', 'instagram'], true)) {
            DB::table('persons')->where('id', $person->id)->update(['preferred_channel' => in_array($channel, ConsentRegistry::PREFERRED, true) ? $channel : null]);
        }

        $pipelineId = (int) ($this->settings->get('chatwoot.pipeline_id') ?: DB::table('lead_pipelines')->where('is_default', 1)->value('id') ?: DB::table('lead_pipelines')->value('id'));
        $stageId = (int) DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipelineId)->orderBy('sort_order')->value('id');

        $lead = Lead::create([
            'title' => trans('communications::app.chatwoot.lead-title', ['name' => $name, 'channel' => trans('communications::app.chatwoot.channels.'.$channel)]),
            'person_id' => $person->id,
            'user_id' => $ownerId,
            'lead_pipeline_id' => $pipelineId,
            'lead_pipeline_stage_id' => $stageId,
            'lead_source_id' => $this->settings->get('chatwoot.lead_source_id'),
            'chatwoot_conversation_id' => $conversation['id'] ?? null,
            'chatwoot_inbox_id' => $conversation['inbox_id'] ?? null,
        ]);

        Event::dispatch('lead.create.after', $lead);

        return [$person, $lead];
    }

    protected function applyKeywords(CommunicationMessage $message, int $personId): void
    {
        $word = Str::of((string) $message->content)->lower()->trim()->trim('.!¡')->value();
        $channel = self::CONSENT_CHANNELS[$message->channel] ?? null;

        if (! $channel || $word === '') {
            return;
        }

        if (in_array($word, self::STOP_WORDS, true)) {
            $this->consents->record($personId, [$channel], ConsentRegistry::REVOKED, $channel === 'email' ? 'unsubscribe' : 'sms_stop', $message->content);
        } elseif (in_array($word, self::START_WORDS, true)) {
            $this->consents->record($personId, [$channel], ConsentRegistry::GRANTED, 'client_request', $message->content);
        }
    }

    protected function notifyOwner(CommunicationMessage $message, Person $person, ?Lead $lead): void
    {
        $notifier = 'Webkul\\Teamwork\\Services\\Notifier';
        $ownerId = $lead?->user_id ?: $person->user_id ?: $this->defaultOwnerId();

        if (! class_exists($notifier) || ! $ownerId) {
            return;
        }

        app($notifier)->notify(
            [$ownerId],
            $notifier::RECORD_ACTIVITY,
            trans('communications::app.chatwoot.notification', ['name' => $person->name, 'channel' => trans('communications::app.chatwoot.channels.'.$message->channel)]),
            Str::limit((string) $message->content, 200),
            route('admin.communications.persons.show', $person->id, false)
        );
    }

    protected function defaultOwnerId(): ?int
    {
        return (int) $this->settings->get('chatwoot.owner_id')
            ?: (int) DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('roles.permission_type', 'all')->where('users.status', 1)->orderBy('users.id')->value('users.id')
            ?: null;
    }

    protected function time($value): Carbon
    {
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone')) : now();
    }
}
