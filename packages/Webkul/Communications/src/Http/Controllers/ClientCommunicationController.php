<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\CommunicationMessage;
use Webkul\Communications\Services\ChatwootAccount;
use Webkul\Communications\Services\ClientTimeline;
use Webkul\Communications\Services\ConsentRegistry;
use Webkul\Lead\Services\ChatwootService;

class ClientCommunicationController extends Controller
{
    public function __construct(protected ClientTimeline $timeline) {}

    /**
     * One client's communications, filterable by channel, direction and text, and sortable.
     */
    public function person(int $id, ChatwootAccount $chatwoot): View
    {
        $person = DB::table('persons')->where('id', $id)->first(['id', 'name', 'emails', 'contact_numbers']);

        abort_unless($person, 404);

        $all = $this->timeline->forPerson($id);

        $channel = request('channel');
        $direction = request('direction');
        $search = trim((string) request('q'));
        $sort = request('sort') === 'oldest' ? 'oldest' : 'newest';

        $entries = $all
            ->when(in_array($channel, ClientTimeline::CHANNELS, true), fn ($items) => $items->where('channel', $channel))
            ->when(in_array($direction, ['in', 'out'], true), fn ($items) => $items->where('direction', $direction))
            ->when($search !== '', fn ($items) => $items->filter(
                fn ($entry) => str_contains(mb_strtolower(($entry['title'] ?? '').' '.($entry['body'] ?? '').' '.($entry['author'] ?? '')), mb_strtolower($search))
            ))
            ->when($sort === 'oldest', fn ($items) => $items->sortBy('at'))
            ->values();

        return view('communications::person', [
            'person' => $person,
            'entries' => $entries,
            'counts' => $all->countBy('channel'),
            'total' => $all->count(),
            'lastContact' => $all->first()['at'] ?? null,
            'filters' => compact('channel', 'direction', 'search', 'sort'),
            'conversation' => $chatwoot->isConfigured() ? $this->latestConversation($id) : null,
            'canReply' => bouncer()->hasPermission('contacts.persons.consent'),
        ]);
    }

    /**
     * Reply to the client's latest chat conversation (WhatsApp, SMS, Telegram…).
     */
    public function reply(Request $request, int $id, ChatwootService $chatwoot, ConsentRegistry $consents): RedirectResponse
    {
        abort_unless(DB::table('persons')->where('id', $id)->exists(), 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $conversation = $this->latestConversation($id);

        if (! $conversation || ! $chatwoot->isConfigured()) {
            return back()->with('error', trans('communications::app.reply.no-conversation'));
        }

        if (in_array($conversation->channel, ConsentRegistry::CHANNELS, true) && ! $consents->allows($id, $conversation->channel, 'transactional')) {
            return back()->with('error', trans('communications::app.reply.consent-revoked'));
        }

        $sent = $chatwoot->sendMessage((int) $conversation->conversation_id, $data['message']);

        if (! $sent || empty($sent['id'])) {
            return back()->withInput()->with('error', trans('communications::app.reply.failed'));
        }

        // Logged now so it shows at once; the webhook echo is ignored as a duplicate.
        CommunicationMessage::firstOrCreate(['provider' => 'chatwoot', 'external_id' => (string) $sent['id']], [
            'conversation_id' => $conversation->conversation_id,
            'inbox_id' => $conversation->inbox_id,
            'channel' => $conversation->channel,
            'direction' => 'out',
            'person_id' => $id,
            'lead_id' => $conversation->lead_id,
            'user_id' => auth()->guard('user')->id(),
            'sender_name' => auth()->guard('user')->user()->name,
            'content' => $data['message'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return back()->with('success', trans('communications::app.reply.sent'));
    }

    /**
     * The client's most recent chat conversation: from logged messages,
     * else from a lead linked to Chatwoot before messages were logged.
     */
    protected function latestConversation(int $personId): ?object
    {
        $message = CommunicationMessage::where('person_id', $personId)
            ->where('provider', 'chatwoot')
            ->whereNotNull('conversation_id')
            ->latest('sent_at')
            ->first(['conversation_id', 'inbox_id', 'channel', 'lead_id']);

        if ($message) {
            return (object) $message->only(['conversation_id', 'inbox_id', 'channel', 'lead_id']);
        }

        $lead = DB::table('leads')->where('person_id', $personId)->whereNotNull('chatwoot_conversation_id')->latest('chatwoot_last_message_at')->first(['id', 'chatwoot_conversation_id', 'chatwoot_inbox_id']);

        return $lead ? (object) [
            'conversation_id' => $lead->chatwoot_conversation_id,
            'inbox_id' => $lead->chatwoot_inbox_id,
            'channel' => app(ChatwootAccount::class)->channelOfInbox((int) $lead->chatwoot_inbox_id) ?? 'chat',
            'lead_id' => $lead->id,
        ] : null;
    }
}
