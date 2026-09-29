<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Services\ClientTimeline;

class ClientCommunicationController extends Controller
{
    public function __construct(protected ClientTimeline $timeline) {}

    /**
     * One client's communications, filterable by channel, direction and text, and sortable.
     */
    public function person(int $id): View
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
        ]);
    }
}
