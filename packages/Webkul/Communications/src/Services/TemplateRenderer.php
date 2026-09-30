<?php

namespace Webkul\Communications\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Communications\Models\CommunicationTemplate;

/**
 * Fills a template for one client: picks the client's language, replaces
 * {variables} and, for email, turns the text into simple HTML.
 */
class TemplateRenderer
{
    /**
     * Variables agents can use, in the order the editor shows them.
     */
    public const VARIABLES = [
        'first_name', 'full_name', 'agent_name', 'agent_email', 'agency_name', 'agency_phone',
        'agency_whatsapp', 'agency_website', 'policy_number', 'carrier', 'plan', 'effective_date', 'renewal_date', 'today',
    ];

    protected const LANGUAGE_OPTIONS = ['english' => 'en', 'inglés' => 'en', 'spanish' => 'es', 'español' => 'es', 'portuguese' => 'es'];

    public function __construct(protected CommunicationSettings $settings) {}

    /**
     * @return array{subject: ?string, body: string, html: ?string, locale: string, channel: string}|null
     */
    public function render(CommunicationTemplate $template, string $channel, int $personId, array $context = []): ?array
    {
        $locale = $this->localeFor($personId);
        $content = $template->contentFor($channel, $locale);

        if (! $content) {
            return null;
        }

        $vars = $this->variables($personId, $context);
        $body = $this->fill($content->body, $vars);

        return [
            'subject' => $content->subject !== null ? $this->fill($content->subject, $vars) : null,
            'body' => $body,
            'html' => $channel === 'email' ? $this->toHtml($body) : null,
            'locale' => $content->locale,
            'channel' => $channel,
        ];
    }

    /**
     * The client's language (Preferred Language field), else the agency's.
     */
    public function localeFor(int $personId): string
    {
        $option = DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->join('attribute_options', 'attribute_options.id', '=', 'attribute_values.integer_value')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'preferred_language')
            ->where('attribute_values.entity_id', $personId)
            ->value('attribute_options.name');

        return self::LANGUAGE_OPTIONS[mb_strtolower(trim((string) $option))] ?? $this->settings->get('agency.locale', 'es');
    }

    /**
     * @param  array{lead_id?: ?int, policy_id?: ?int}  $context
     */
    public function variables(int $personId, array $context = []): array
    {
        $person = DB::table('persons')->where('id', $personId)->first(['name', 'user_id']);
        $lead = ! empty($context['lead_id']) ? DB::table('leads')->where('id', $context['lead_id'])->first(['user_id']) : null;
        $policy = ! empty($context['policy_id'])
            ? DB::table('insurance_policies')->where('id', $context['policy_id'])->first(['policy_number', 'carrier_name', 'plan_name', 'effective_date', 'renewal_date', 'user_id'])
            : null;

        $agentId = $policy?->user_id ?: $lead?->user_id ?: $person?->user_id;
        $agent = $agentId ? DB::table('users')->where('id', $agentId)->first(['name', 'email']) : null;
        $date = fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '';
        $name = trim((string) ($person->name ?? ''));

        return [
            'first_name' => Str::of($name)->before(' ')->value() ?: $name,
            'full_name' => $name,
            'agent_name' => (string) ($agent->name ?? ''),
            'agent_email' => (string) ($agent->email ?? ''),
            'agency_name' => (string) $this->settings->get('agency.name', config('app.name')),
            'agency_phone' => (string) $this->settings->get('agency.phone', ''),
            'agency_whatsapp' => (string) $this->settings->get('agency.whatsapp', ''),
            'agency_website' => (string) $this->settings->get('agency.website', ''),
            'policy_number' => (string) ($policy->policy_number ?? ''),
            'carrier' => (string) ($policy->carrier_name ?? ''),
            'plan' => (string) ($policy->plan_name ?? ''),
            'effective_date' => $date($policy->effective_date ?? null),
            'renewal_date' => $date($policy->renewal_date ?? null),
            'today' => now()->format('d/m/Y'),
        ];
    }

    /**
     * Sample values for the editor preview.
     */
    public function sampleVariables(): array
    {
        return [
            'first_name' => 'María', 'full_name' => 'María González', 'agent_name' => 'Carlos Ruiz', 'agent_email' => 'carlos@agencia.com',
            'agency_name' => (string) $this->settings->get('agency.name', config('app.name')), 'agency_phone' => (string) $this->settings->get('agency.phone', '(305) 555-0100'),
            'agency_whatsapp' => (string) $this->settings->get('agency.whatsapp', '+1 305 555 0100'), 'agency_website' => (string) $this->settings->get('agency.website', 'www.agencia.com'),
            'policy_number' => 'U1234-5678', 'carrier' => 'Ambetter', 'plan' => 'Silver 70 HMO',
            'effective_date' => now()->startOfMonth()->addMonth()->format('d/m/Y'), 'renewal_date' => now()->addYear()->startOfYear()->format('d/m/Y'), 'today' => now()->format('d/m/Y'),
        ];
    }

    public function fill(string $text, array $vars): string
    {
        return strtr($text, collect($vars)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => $value])->all());
    }

    /**
     * Plain text to email HTML: paragraphs, line breaks, **bold** and
     * [links](https://…). Everything else is escaped.
     */
    public function toHtml(string $text): string
    {
        $html = e($text);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" style="color:#4f6bff;">$1</a>', $html);

        return collect(preg_split("/\n\s*\n/", trim($html)))
            ->map(fn ($paragraph) => '<p style="margin:0 0 14px;">'.nl2br(trim($paragraph)).'</p>')
            ->implode("\n");
    }
}
