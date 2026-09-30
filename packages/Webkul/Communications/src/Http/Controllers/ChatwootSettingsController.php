<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Services\ChatwootAccount;
use Webkul\Communications\Services\CommunicationSettings;

class ChatwootSettingsController extends Controller
{
    public function __construct(
        protected CommunicationSettings $settings,
        protected ChatwootAccount $account,
    ) {}

    public function index(): View
    {
        $inboxes = [];
        $error = null;

        if ($this->account->isConfigured()) {
            try {
                $inboxes = $this->account->inboxes(true);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('communications::settings.chatwoot', [
            'values' => [
                // Only saved values: never prefill the cloud defaults from config/chatwoot.php.
                'base_url' => $this->settings->get('chatwoot.base_url', env('CHATWOOT_BASE_URL')),
                'account_id' => $this->settings->get('chatwoot.account_id', env('CHATWOOT_ACCOUNT_ID')),
                'default_inbox_id' => config('chatwoot.default_inbox_id'),
                'owner_id' => $this->settings->get('chatwoot.owner_id'),
                'pipeline_id' => $this->settings->get('chatwoot.pipeline_id'),
                'lead_source_id' => $this->settings->get('chatwoot.lead_source_id'),
            ],
            'hasToken' => (bool) config('chatwoot.api_token'),
            'configured' => $this->account->isConfigured(),
            'inboxes' => $inboxes,
            'error' => $error,
            'webhookUrl' => $this->webhookUrl(),
            'webhookRegisteredAt' => $this->settings->get('chatwoot.webhook_registered_at'),
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'pipelines' => DB::table('lead_pipelines')->orderBy('id')->pluck('name', 'id'),
            'sources' => DB::table('lead_sources')->orderBy('name')->pluck('name', 'id'),
            'messageCount' => DB::table('communication_messages')->where('provider', 'chatwoot')->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'base_url' => ['required', 'url', 'max:255'],
            'account_id' => ['required', 'integer', 'min:1'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'default_inbox_id' => ['nullable', 'integer'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'pipeline_id' => ['nullable', 'integer', 'exists:lead_pipelines,id'],
            'lead_source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
        ]);

        foreach (['base_url', 'account_id', 'default_inbox_id', 'owner_id', 'pipeline_id', 'lead_source_id'] as $key) {
            $this->settings->set("chatwoot.{$key}", $key === 'base_url' ? rtrim($data[$key], '/') : ($data[$key] ?? null));
        }

        // The token field is write-only: left empty, the saved token stays.
        if (! empty($data['api_token'])) {
            $this->settings->set('chatwoot.api_token', trim($data['api_token']));
        }

        $this->settings->applyToConfig();

        try {
            $inboxes = $this->account->inboxes(true);
            $this->account->registerWebhook($this->webhookUrl());
            $this->settings->set('chatwoot.webhook_registered_at', now()->toDateTimeString());

            session()->flash('success', trans('communications::app.chatwoot.connected', ['count' => count($inboxes)]));
        } catch (Throwable $e) {
            session()->flash('error', trans('communications::app.chatwoot.saved-not-connected', ['error' => $e->getMessage()]));
        }

        return redirect()->route('admin.settings.communications.chatwoot.index');
    }

    public function disconnect(): RedirectResponse
    {
        $this->settings->set('chatwoot.api_token', null);
        $this->settings->set('chatwoot.webhook_registered_at', null);

        session()->flash('success', trans('communications::app.chatwoot.disconnected'));

        return redirect()->route('admin.settings.communications.chatwoot.index');
    }

    protected function webhookUrl(): string
    {
        return route('communications.chatwoot.webhook', ['token' => $this->settings->webhookToken()]);
    }
}
