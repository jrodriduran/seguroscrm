<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\CommunicationTemplate;
use Webkul\Communications\Services\CommunicationSettings;
use Webkul\Communications\Services\TemplateRenderer;

class TemplateController extends Controller
{
    public function __construct(protected CommunicationSettings $settings) {}

    public function index(): View
    {
        return view('communications::templates.index', [
            'templates' => CommunicationTemplate::with('contents')->orderBy('category')->orderBy('name')->get(),
            'usage' => DB::table('communication_sequence_steps')->whereNotNull('template_id')->selectRaw('template_id, count(*) as total')->groupBy('template_id')->pluck('total', 'template_id'),
            'agency' => collect(['name', 'phone', 'whatsapp', 'website', 'address', 'locale'])->mapWithKeys(fn ($key) => [$key => $this->settings->get("agency.{$key}")]),
            'sending' => [
                'from' => $this->settings->get('sequences.send_from', 9),
                'until' => $this->settings->get('sequences.send_until', 19),
                'cap' => $this->settings->get('sequences.weekly_cap', 3),
            ],
        ]);
    }

    public function create(TemplateRenderer $renderer): View
    {
        return $this->form(new CommunicationTemplate(['category' => 'general', 'purpose' => 'transactional', 'is_active' => true]), $renderer);
    }

    public function edit(int $id, TemplateRenderer $renderer): View
    {
        return $this->form(CommunicationTemplate::with('contents')->findOrFail($id), $renderer);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = CommunicationTemplate::create($this->validated($request) + ['agency_id' => auth()->guard('user')->user()->agency_id]);

        $this->saveContents($template, $request);

        session()->flash('success', trans('communications::app.templates.saved'));

        return redirect()->route('admin.communications.templates.edit', $template->id);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $template = CommunicationTemplate::findOrFail($id);
        $template->update($this->validated($request));

        $this->saveContents($template, $request);

        session()->flash('success', trans('communications::app.templates.saved'));

        return redirect()->route('admin.communications.templates.edit', $template->id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = CommunicationTemplate::findOrFail($id);

        if (DB::table('communication_sequence_steps')->where('template_id', $id)->exists()) {
            session()->flash('error', trans('communications::app.templates.in-use'));

            return redirect()->route('admin.communications.templates.index');
        }

        $template->delete();

        session()->flash('success', trans('communications::app.templates.deleted'));

        return redirect()->route('admin.communications.templates.index');
    }

    /**
     * Agency details used in messages, and sending rules.
     */
    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'agency.name' => ['nullable', 'string', 'max:120'],
            'agency.phone' => ['nullable', 'string', 'max:40'],
            'agency.whatsapp' => ['nullable', 'string', 'max:40'],
            'agency.website' => ['nullable', 'string', 'max:120'],
            'agency.address' => ['nullable', 'string', 'max:255'],
            'agency.locale' => ['nullable', Rule::in(CommunicationTemplate::LOCALES)],
            'sequences.send_from' => ['required', 'integer', 'between:0,23'],
            'sequences.send_until' => ['required', 'integer', 'between:1,24', 'gt:sequences.send_from'],
            'sequences.weekly_cap' => ['required', 'integer', 'between:0,20'],
        ]);

        foreach (['agency', 'sequences'] as $group) {
            foreach ($data[$group] ?? [] as $key => $value) {
                $this->settings->set("{$group}.{$key}", $value);
            }
        }

        session()->flash('success', trans('communications::app.templates.settings-saved'));

        return redirect()->route('admin.communications.templates.index');
    }

    protected function form(CommunicationTemplate $template, TemplateRenderer $renderer): View
    {
        return view('communications::templates.edit', [
            'template' => $template,
            'variables' => TemplateRenderer::VARIABLES,
            'sample' => $renderer->sampleVariables(),
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(CommunicationTemplate::CATEGORIES)],
            'purpose' => ['required', Rule::in(CommunicationTemplate::PURPOSES)],
            'contents' => ['nullable', 'array'],
            'contents.*.*.subject' => ['nullable', 'string', 'max:200'],
            'contents.*.*.body' => ['nullable', 'string', 'max:5000'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    /**
     * One row per channel and language that has text; empty ones are removed.
     */
    protected function saveContents(CommunicationTemplate $template, Request $request): void
    {
        foreach (CommunicationTemplate::CHANNELS as $channel) {
            foreach (CommunicationTemplate::LOCALES as $locale) {
                $body = trim((string) $request->input("contents.{$channel}.{$locale}.body"));

                if ($body === '') {
                    $template->contents()->where('channel', $channel)->where('locale', $locale)->delete();

                    continue;
                }

                $template->contents()->updateOrCreate(['channel' => $channel, 'locale' => $locale], [
                    'subject' => $channel === 'email' ? trim((string) $request->input("contents.{$channel}.{$locale}.subject")) ?: null : null,
                    'body' => $body,
                ]);
            }
        }
    }
}
