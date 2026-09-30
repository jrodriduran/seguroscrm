<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Services\ConsentRegistry;
use Webkul\Communications\Services\ZipLookup;

class PersonContactController extends Controller
{
    public function __construct(protected ConsentRegistry $consents) {}

    /**
     * Preferred channel.
     */
    public function preferences(Request $request, int $id): RedirectResponse
    {
        abort_unless(DB::table('persons')->where('id', $id)->exists(), 404);

        $data = $request->validate([
            'preferred_channel' => ['nullable', Rule::in(ConsentRegistry::PREFERRED)],
        ]);

        DB::table('persons')->where('id', $id)->update([
            'preferred_channel' => $data['preferred_channel'] ?? null,
            'updated_at' => now(),
        ]);

        session()->flash('success', trans('communications::app.contact.saved'));

        return redirect()->back();
    }

    /**
     * Grant or revoke consent on one channel, or on all of them.
     */
    public function consent(Request $request, int $id): RedirectResponse
    {
        abort_unless(DB::table('persons')->where('id', $id)->exists(), 404);

        $data = $request->validate([
            'channel' => ['required', Rule::in([...ConsentRegistry::CHANNELS, 'all'])],
            'status' => ['required', Rule::in([ConsentRegistry::GRANTED, ConsentRegistry::REVOKED])],
            'source' => ['required', Rule::in(ConsentRegistry::SOURCES)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $channels = $data['channel'] === 'all' ? ConsentRegistry::CHANNELS : [$data['channel']];

        $this->consents->record($id, $channels, $data['status'], $data['source'], $data['note'] ?? null);

        session()->flash('success', trans('communications::app.contact.consent-saved'));

        return redirect()->back();
    }

    /**
     * City and state for a ZIP code (address forms fill themselves).
     */
    public function zip(string $zip, ZipLookup $lookup): JsonResponse
    {
        $result = $lookup->lookup($zip);

        return response()->json($result ?? ['message' => 'not found'], $result ? 200 : 404);
    }
}
