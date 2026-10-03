<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Milestones the CRM can verify on its own, from the lead and its client.
 * Each check answers "is this done for this lead?".
 */
class MilestoneChecks
{
    public const CHECKS = [
        'contact_info', 'email', 'phone', 'dob', 'address', 'call_logged', 'household', 'income',
        'quote', 'cms_consent', 'soa_signed', 'contact_consent', 'documents', 'doctors_rx',
        'sep_verified', 'policy', 'binder_paid', 'effectuated',
    ];

    /**
     * Results cached per lead for the current request.
     */
    protected array $cache = [];

    public function passes(string $check, object $lead): bool
    {
        $key = $lead->id.':'.$check;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            $result = $this->evaluate($check, $lead);
        } catch (Throwable) {
            $result = false;
        }

        return $this->cache[$key] = $result;
    }

    public function forget(int $leadId): void
    {
        $this->cache = array_filter($this->cache, fn ($key) => ! str_starts_with($key, $leadId.':'), ARRAY_FILTER_USE_KEY);
    }

    protected function evaluate(string $check, object $lead): bool
    {
        $personId = (int) ($lead->person_id ?? 0);
        $person = $personId ? DB::table('persons')->where('id', $personId)->first(['emails', 'contact_numbers']) : null;
        $hasValue = fn ($json) => collect(json_decode((string) $json, true))->contains(fn ($item) => trim((string) ($item['value'] ?? '')) !== '');

        return match ($check) {
            'contact_info' => $person && ($hasValue($person->emails) || $hasValue($person->contact_numbers)),
            'email' => $person && $hasValue($person->emails),
            'phone' => $person && $hasValue($person->contact_numbers),
            'dob' => $this->attribute('persons', 'dob', $personId, 'date_value'),
            'address' => $this->attribute('persons', 'address', $personId, 'json_value'),
            'income' => $this->attribute('leads', 'annual_income', (int) $lead->id, 'float_value')
                || $this->exists('lead_tax_households', fn ($q) => $q->where('lead_id', $lead->id)->where('projected_annual_income', '>', 0)),
            'call_logged' => DB::table('activities')
                ->where('type', 'call')
                ->where('is_done', 1)
                ->where(fn ($q) => $q->whereIn('id', DB::table('lead_activities')->where('lead_id', $lead->id)->select('activity_id'))
                    ->when($personId, fn ($q2) => $q2->orWhereIn('id', DB::table('person_activities')->where('person_id', $personId)->select('activity_id'))))
                ->exists(),
            'household' => $this->exists('lead_household_members', fn ($q) => $q->where('lead_id', $lead->id)),
            'quote' => $this->exists('lead_quotes', fn ($q) => $q->where('lead_id', $lead->id)),
            'cms_consent' => $this->exists('lead_consents', fn ($q) => $q->where('lead_id', $lead->id)->where('status', 'signed')),
            'soa_signed' => $this->exists('lead_medicare_soas', fn ($q) => $q->where('lead_id', $lead->id)->where('status', 'signed')),
            'contact_consent' => (bool) ($lead->has_tcpa_consent ?? false)
                || ($personId && $this->exists('contact_consents', fn ($q) => $q->where('person_id', $personId)->whereIn('channel', ['call', 'sms', 'whatsapp'])->where('status', 'granted'))),
            'documents' => $this->exists('lead_dmi_documents', fn ($q) => $q->where('lead_id', $lead->id)->whereNotNull('file_path')),
            'doctors_rx' => $this->exists('lead_doctor_networks', fn ($q) => $q->where('lead_id', $lead->id))
                || $this->exists('lead_rx_medications', fn ($q) => $q->where('lead_id', $lead->id)),
            'sep_verified' => $this->exists('lead_sep_qualifications', fn ($q) => $q->where('lead_id', $lead->id)->whereNotNull('verified_at')),
            'policy' => $this->exists('insurance_policies', fn ($q) => $q->where('lead_id', $lead->id)),
            'binder_paid' => $this->exists('insurance_policies', fn ($q) => $q->where('lead_id', $lead->id)->whereNotNull('binder_paid_at')),
            'effectuated' => $this->exists('insurance_policies', fn ($q) => $q->where('lead_id', $lead->id)->whereNotNull('effectuation_date')),
            default => false,
        };
    }

    protected function exists(string $table, callable $where): bool
    {
        return Schema::hasTable($table) && $where(DB::table($table))->exists();
    }

    protected function attribute(string $entity, string $code, int $entityId, string $column): bool
    {
        if (! $entityId) {
            return false;
        }

        $value = DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.entity_type', $entity)
            ->where('attributes.code', $code)
            ->where('attribute_values.entity_id', $entityId)
            ->value('attribute_values.'.$column);

        if ($column === 'json_value') {
            return (bool) array_filter((array) json_decode((string) $value, true));
        }

        return $value !== null && $value !== '' && $value !== 0.0;
    }
}
