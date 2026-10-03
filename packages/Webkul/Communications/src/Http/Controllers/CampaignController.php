<?php

namespace Webkul\Communications\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Audience;
use Webkul\Communications\Models\Campaign;
use Webkul\Communications\Models\CommunicationTemplate;
use Webkul\Communications\Services\AudienceQuery;
use Webkul\Communications\Services\CampaignRunner;
use Webkul\Communications\Services\CommunicationSettings;
use Webkul\Communications\Services\Occasions;
use Webkul\Communications\Services\SequenceEngine;
use Webkul\Communications\Services\TemplateRenderer;

class CampaignController extends Controller
{
    public function __construct(protected CampaignRunner $runner) {}

    public function index(Occasions $occasions): View
    {
        return view('communications::campaigns.index', [
            'campaigns' => Campaign::with(['audience:id,name', 'template:id,name'])->latest('id')->get()->map(function (Campaign $campaign) {
                $campaign->stats = $this->runner->stats($campaign);

                return $campaign;
            }),
            'occasions' => $occasions->upcoming(),
            'existing' => Campaign::whereNotNull('occasion')->whereIn('status', ['draft', 'scheduled', 'sending', 'sent'])->get(['occasion', 'scheduled_at'])
                ->filter(fn ($c) => $c->scheduled_at === null || $c->scheduled_at->isFuture() || $c->scheduled_at->gt(now()->subMonths(2)))
                ->pluck('occasion')->all(),
        ]);
    }

    public function create(Request $request, Occasions $occasions, SequenceEngine $engine): View
    {
        $campaign = new Campaign(['channel' => 'preferred', 'audience_id' => $request->integer('audience') ?: null]);

        if ($code = $request->query('occasion')) {
            $occasion = collect($occasions->upcoming())->firstWhere('code', $code);

            if ($occasion) {
                $campaign->fill([
                    'name' => trans('communications::app.occasions.names.'.$code).' '.$occasion['date']->year,
                    'occasion' => $code,
                    'audience_id' => Audience::where('code', $occasion['audience'])->value('id'),
                    'template_id' => CommunicationTemplate::where('code', $occasion['template'])->value('id'),
                    'scheduled_at' => $occasion['date']->copy()->setTimezone($engine->timezone())->setTime(10, 0)->setTimezone(config('app.timezone')),
                ]);
            }
        }

        return $this->form($campaign);
    }

    public function edit(int $id): View
    {
        $campaign = Campaign::findOrFail($id);

        abort_unless($campaign->isEditable(), 404);

        return $this->form($campaign);
    }

    public function show(int $id): View
    {
        $campaign = Campaign::with(['audience', 'template'])->findOrFail($id);

        return view('communications::campaigns.show', [
            'campaign' => $campaign,
            'stats' => $this->runner->stats($campaign),
            'recipients' => $campaign->recipients()->join('persons', 'persons.id', '=', 'communication_campaign_recipients.person_id')
                ->orderByRaw("field(communication_campaign_recipients.status, 'failed', 'skipped', 'pending', 'sent', 'cancelled')")
                ->limit(200)
                ->get(['communication_campaign_recipients.*', 'persons.name']),
            'audienceCount' => $campaign->audience ? app(AudienceQuery::class)->count($campaign->audience->rules ?? []) : 0,
            'canLaunch' => $this->canLaunch(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaign = Campaign::create($this->validated($request) + [
            'agency_id' => auth()->guard('user')->user()->agency_id,
            'created_by' => auth()->guard('user')->id(),
            'status' => Campaign::DRAFT,
        ]);

        session()->flash('success', trans('communications::app.campaigns.saved'));

        return redirect()->route('admin.communications.campaigns.show', $campaign->id);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $campaign = Campaign::findOrFail($id);

        abort_unless($campaign->isEditable(), 404);

        // Changing a scheduled campaign sends it back to draft (recipients are re-read on launch).
        $campaign->update($this->validated($request) + ['status' => Campaign::DRAFT]);
        $campaign->recipients()->delete();

        session()->flash('success', trans('communications::app.campaigns.saved'));

        return redirect()->route('admin.communications.campaigns.show', $campaign->id);
    }

    /**
     * Agency owner approves: recipients are frozen and it goes out on its date.
     */
    public function launch(int $id): RedirectResponse
    {
        abort_unless($this->canLaunch(), 403);

        $campaign = Campaign::with('audience')->findOrFail($id);

        abort_unless($campaign->status === Campaign::DRAFT, 422);

        if (! $campaign->template_id || ! $campaign->audience_id) {
            session()->flash('error', trans('communications::app.campaigns.incomplete'));

            return back();
        }

        $count = $this->runner->schedule($campaign, auth()->guard('user')->id());

        session()->flash('success', trans('communications::app.campaigns.scheduled', ['count' => $count, 'when' => core()->formatDate($campaign->scheduled_at, 'd M Y H:i')]));

        return back();
    }

    public function cancel(int $id): RedirectResponse
    {
        abort_unless($this->canLaunch(), 403);

        $campaign = Campaign::findOrFail($id);

        if (in_array($campaign->status, [Campaign::SCHEDULED, Campaign::SENDING], true)) {
            $this->runner->cancel($campaign);
        }

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $campaign = Campaign::findOrFail($id);

        abort_unless(in_array($campaign->status, [Campaign::DRAFT, Campaign::CANCELLED], true), 422);

        $campaign->delete();

        session()->flash('success', trans('communications::app.campaigns.deleted'));

        return redirect()->route('admin.communications.campaigns.index');
    }

    /**
     * Send the email version to the signed-in user, with sample data.
     */
    public function test(int $id, TemplateRenderer $renderer, CommunicationSettings $settings): RedirectResponse
    {
        $campaign = Campaign::with('template.contents')->findOrFail($id);
        $user = auth()->guard('user')->user();
        $content = $campaign->template?->contentFor('email', $settings->get('agency.locale', 'es'));

        if (! $content || ! $user->email) {
            session()->flash('error', trans('communications::app.campaigns.no-email-version'));

            return back();
        }

        $vars = $renderer->sampleVariables();
        $html = view('communications::emails.message', [
            'body' => $renderer->toHtml($renderer->fill($content->body, $vars)),
            'agency' => $vars,
            'address' => $settings->get('agency.address'),
            'unsubscribe' => null,
            'locale' => $content->locale,
        ])->render();

        try {
            Mail::html($html, fn (Message $message) => $message->to($user->email)->subject('[TEST] '.$renderer->fill((string) $content->subject, $vars)));
            session()->flash('success', trans('communications::app.campaigns.test-sent', ['email' => $user->email]));
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        return back();
    }

    protected function form(Campaign $campaign): View
    {
        return view('communications::campaigns.edit', [
            'campaign' => $campaign,
            'audiences' => Audience::orderBy('name')->pluck('name', 'id'),
            'templates' => CommunicationTemplate::with('contents')->where('is_active', true)->orderBy('name')->get(),
            'timezone' => app(SequenceEngine::class)->timezone(),
        ]);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'audience_id' => ['required', 'integer', 'exists:communication_audiences,id'],
            'template_id' => ['required', 'integer', 'exists:communication_templates,id'],
            'channel' => ['required', Rule::in(Campaign::CHANNELS)],
            'occasion' => ['nullable', Rule::in(array_keys(Occasions::ALL))],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        // The date is typed in the agency time zone.
        $data['scheduled_at'] = ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'], app(SequenceEngine::class)->timezone())->setTimezone(config('app.timezone'))
            : null;

        return $data;
    }

    protected function canLaunch(): bool
    {
        $user = auth()->guard('user')->user();

        if ($user?->role?->permission_type === 'all') {
            return true;
        }

        $scope = 'Webkul\\Teamwork\\Services\\TeamScope';

        return $user && class_exists($scope) && app($scope)->isMasterAgent($user);
    }
}
