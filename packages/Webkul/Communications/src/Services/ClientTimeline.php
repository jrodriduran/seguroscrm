<?php

namespace Webkul\Communications\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Communications\Models\CommunicationMessage;
use Webkul\Lead\Services\ChatwootService;

/**
 * Every interaction with one client, from every channel, as one timeline:
 * emails, calls, meetings and notes logged in the CRM, and chat messages
 * (WhatsApp, Instagram, Messenger, SMS, Telegram…) that reach us through
 * Chatwoot conversations linked to the client's leads.
 */
class ClientTimeline
{
    /**
     * Channels shown as filters, in order.
     */
    const CHANNELS = ['chat', 'email', 'call', 'meeting', 'note', 'file'];

    public function __construct(protected ChatwootService $chatwoot) {}

    public function forPerson(int $personId): Collection
    {
        $leadIds = DB::table('leads')->where('person_id', $personId)->pluck('id')->all();

        return $this->emails($personId, $leadIds)
            ->merge($this->activities($personId, $leadIds))
            ->merge($logged = $this->loggedMessages($personId, $leadIds))
            ->merge($this->chats($leadIds, $logged->pluck('conversation_id')->filter()->unique()->all()))
            ->sortByDesc('at')
            ->values();
    }

    /**
     * Chat messages received through webhooks and kept in the CRM.
     */
    protected function loggedMessages(int $personId, array $leadIds): Collection
    {
        return CommunicationMessage::with('user:id,name')
            ->where(fn ($query) => $query->where('person_id', $personId)->when($leadIds, fn ($q) => $q->orWhereIn('lead_id', $leadIds)))
            ->latest('sent_at')
            ->limit(500)
            ->get()
            ->map(fn (CommunicationMessage $message) => [
                'channel' => 'chat',
                'direction' => $message->direction,
                'author' => $message->user?->name ?? $message->sender_name,
                'title' => Lang::has($key = 'communications::app.chatwoot.channels.'.$message->channel) ? trans($key) : $message->channel,
                'body' => Str::limit((string) $message->content, 400).($message->attachments ? ' 📎' : ''),
                'at' => $message->sent_at ?? $message->created_at,
                'status' => $message->status,
                'conversation_id' => $message->conversation_id,
                'url' => $message->conversation_id && $this->chatwoot->isConfigured() ? $this->chatwoot->getConversationUrl((int) $message->conversation_id) : null,
            ]);
    }

    protected function emails(int $personId, array $leadIds): Collection
    {
        return DB::table('emails')
            ->where(fn ($query) => $query->where('person_id', $personId)->when($leadIds, fn ($q) => $q->orWhereIn('lead_id', $leadIds)))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'subject', 'reply', 'user_type', 'name', 'from', 'created_at'])
            ->map(fn ($email) => [
                'channel' => 'email',
                'direction' => $email->user_type === 'admin' ? 'out' : 'in',
                'author' => $email->name ?: collect(json_decode((string) $email->from, true))->first(),
                'title' => $email->subject,
                'body' => Str::limit(trim(strip_tags((string) $email->reply)), 400),
                'at' => Carbon::parse($email->created_at),
                'url' => route('admin.mail.view', ['route' => 'inbox', 'id' => $email->id]),
            ]);
    }

    protected function activities(int $personId, array $leadIds): Collection
    {
        $personActivityIds = DB::table('person_activities')->where('person_id', $personId)->pluck('activity_id');
        $leadActivities = DB::table('lead_activities')->whereIn('lead_id', $leadIds ?: [0])->pluck('lead_id', 'activity_id');

        return DB::table('activities')
            ->leftJoin('users', 'users.id', '=', 'activities.user_id')
            ->whereIn('activities.id', $personActivityIds->merge($leadActivities->keys())->unique()->all() ?: [0])
            ->whereIn('activities.type', ['call', 'meeting', 'lunch', 'note', 'email', 'file'])
            ->orderByDesc('activities.created_at')
            ->limit(300)
            ->get(['activities.id', 'activities.type', 'activities.title', 'activities.comment', 'activities.schedule_from', 'activities.created_at', 'users.name as user_name'])
            ->map(function ($activity) use ($leadActivities, $personId) {
                $leadId = $leadActivities->get($activity->id);

                return [
                    'channel' => match ($activity->type) {
                        'lunch' => 'meeting',
                        'email' => 'email',
                        default => $activity->type,
                    },
                    'direction' => 'out',
                    'author' => $activity->user_name,
                    'title' => $activity->title,
                    'body' => Str::limit(trim(strip_tags((string) $activity->comment)), 400),
                    'at' => Carbon::parse($activity->schedule_from ?: $activity->created_at),
                    'url' => $leadId ? route('admin.leads.view', $leadId) : route('admin.contacts.persons.view', $personId),
                ];
            });
    }

    /**
     * Messages from Chatwoot conversations linked to the client's leads that
     * were never logged (linked before webhooks were set up): read live.
     */
    protected function chats(array $leadIds, array $loggedConversations = []): Collection
    {
        if (! $leadIds || ! $this->chatwoot->isConfigured()) {
            return collect();
        }

        return DB::table('leads')
            ->whereIn('id', $leadIds)
            ->whereNotNull('chatwoot_conversation_id')
            ->whereNotIn('chatwoot_conversation_id', $loggedConversations ?: [0])
            ->limit(10)
            ->get(['id', 'chatwoot_conversation_id'])
            ->flatMap(function ($lead) {
                return collect($this->chatwoot->getMessages((int) $lead->chatwoot_conversation_id))
                    // 0 = incoming, 1 = outgoing; skip system/activity messages and private notes.
                    ->filter(fn ($message) => in_array($message['message_type'] ?? null, [0, 1], true) && empty($message['private']))
                    ->map(fn ($message) => [
                        'channel' => 'chat',
                        'direction' => ($message['message_type'] ?? 0) === 1 ? 'out' : 'in',
                        'author' => $message['sender']['name'] ?? null,
                        'title' => null,
                        'body' => Str::limit((string) ($message['content'] ?? ''), 400),
                        'at' => Carbon::createFromTimestamp((int) ($message['created_at'] ?? time())),
                        'url' => $this->chatwoot->getConversationUrl((int) $lead->chatwoot_conversation_id),
                    ]);
            });
    }
}
