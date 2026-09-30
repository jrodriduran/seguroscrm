<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Communications\Services\CommunicationSettings;
use Webkul\Communications\Services\ConsentRegistry;
use Webkul\Communications\Services\TemplateRenderer;

/**
 * Public, signed link in marketing emails. GET asks for confirmation;
 * POST (the button, or mail apps' one-click unsubscribe) withdraws consent.
 */
class UnsubscribeController extends Controller
{
    public function show(Request $request, int $person, TemplateRenderer $renderer, CommunicationSettings $settings)
    {
        abort_unless(DB::table('persons')->where('id', $person)->exists(), 404);

        app()->setLocale($renderer->localeFor($person));

        return view('communications::unsubscribe', [
            'agency' => $settings->get('agency.name', config('app.name')),
            'done' => false,
            'action' => $request->fullUrl(),
        ]);
    }

    public function confirm(int $person, ConsentRegistry $consents, TemplateRenderer $renderer, CommunicationSettings $settings)
    {
        abort_unless(DB::table('persons')->where('id', $person)->exists(), 404);

        $consents->record($person, ['email'], ConsentRegistry::REVOKED, 'unsubscribe', null, null);

        app()->setLocale($renderer->localeFor($person));

        return view('communications::unsubscribe', [
            'agency' => $settings->get('agency.name', config('app.name')),
            'done' => true,
            'action' => null,
        ]);
    }
}
