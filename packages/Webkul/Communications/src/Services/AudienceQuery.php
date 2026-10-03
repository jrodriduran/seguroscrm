<?php

namespace Webkul\Communications\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Turns list conditions into a query over contacts. Every condition must
 * match. Conditions look at the contact, their household, policies, leads,
 * consent and recent communication.
 */
class AudienceQuery
{
    /**
     * Field => kind of value the editor asks for.
     */
    public const FIELDS = [
        'gender' => 'select',
        'age_min' => 'number',
        'age_max' => 'number',
        'birthday_month' => 'month',
        'language' => 'select',
        'marital_status' => 'select',
        'state' => 'text',
        'city' => 'text',
        'zip' => 'text',
        'has_children' => 'yesno',
        'children_under' => 'number',
        'has_spouse' => 'yesno',
        'tag' => 'tag',
        'owner' => 'user',
        'policy_status' => 'policy_status',
        'policy_market' => 'text',
        'carrier' => 'text',
        'renewal_month' => 'month',
        'lead_stage' => 'stage',
        'preferred_channel' => 'channel',
        'consent' => 'consent_channel',
        'no_contact_days' => 'number',
    ];

    public const POLICY_STATUSES = ['active', 'pending', 'grace_period_1', 'grace_period_2_3', 'cancelled', 'renewed'];

    /**
     * Contacts matching all rules, limited to what the user may see.
     */
    public function query(array $rules, ?array $userIds = null): Builder
    {
        $query = DB::table('persons')->select('persons.id', 'persons.name', 'persons.emails', 'persons.contact_numbers', 'persons.preferred_channel', 'persons.user_id');

        if ($userIds) {
            $query->whereIn('persons.user_id', $userIds);
        }

        foreach ($rules as $rule) {
            $field = $rule['field'] ?? null;
            $value = $rule['value'] ?? null;

            if (! isset(self::FIELDS[$field]) || $value === null || $value === '') {
                continue;
            }

            $this->apply($query, $field, $value);
        }

        return $query;
    }

    public function count(array $rules, ?array $userIds = null): int
    {
        return $this->query($rules, $userIds)->count();
    }

    protected function apply(Builder $query, string $field, $value): void
    {
        $now = now();

        match ($field) {
            'gender', 'language', 'marital_status' => $this->selectAttribute($query, ['gender' => 'gender', 'language' => 'preferred_language', 'marital_status' => 'marital_status'][$field], (string) $value),
            'age_min' => $this->dateAttribute($query, fn ($q) => $q->where('attribute_values.date_value', '<=', $now->copy()->subYears((int) $value)->toDateString())),
            'age_max' => $this->dateAttribute($query, fn ($q) => $q->where('attribute_values.date_value', '>', $now->copy()->subYears((int) $value + 1)->toDateString())),
            'birthday_month' => $this->dateAttribute($query, fn ($q) => $q->whereMonth('attribute_values.date_value', $value === 'current' ? $now->month : (int) $value)),
            'state' => $this->addressOrLead($query, 'state', (string) $value),
            'city' => $this->address($query, fn ($q) => $q->whereRaw("json_unquote(json_extract(attribute_values.json_value, '$.city')) like ?", [$this->like($value).'%'])),
            'zip' => $this->address($query, fn ($q) => $q->whereRaw("json_unquote(json_extract(attribute_values.json_value, '$.postcode')) like ?", [$this->like($value).'%'])),
            'has_children' => $this->household($query, fn ($q) => $q->where('relationship', 'child'), $value === 'yes'),
            'children_under' => $this->household($query, fn ($q) => $q->where('relationship', 'child')->where('date_of_birth', '>', $now->copy()->subYears((int) $value)->toDateString()), true),
            'has_spouse' => $this->household($query, fn ($q) => $q->where('relationship', 'spouse'), $value === 'yes'),
            'tag' => $query->whereExists(fn ($q) => $q->from('person_tags')->whereColumn('person_tags.person_id', 'persons.id')->where('person_tags.tag_id', (int) $value)),
            'owner' => $query->where('persons.user_id', (int) $value),
            'policy_status' => $this->policy($query, fn ($q) => $q->where('status', $value)),
            'policy_market' => $this->policy($query, fn ($q) => $q->where('market_type', 'like', '%'.$this->like($value).'%')),
            'carrier' => $this->policy($query, fn ($q) => $q->where('carrier_name', 'like', '%'.$this->like($value).'%')),
            'renewal_month' => $this->policy($query, fn ($q) => $q->whereMonth('renewal_date', (int) $value)),
            'lead_stage' => $query->whereExists(fn ($q) => $q->from('leads')->whereColumn('leads.person_id', 'persons.id')->where('leads.lead_pipeline_stage_id', (int) $value)),
            'preferred_channel' => $query->where('persons.preferred_channel', $value),
            'consent' => $query->whereExists(fn ($q) => $q->from('contact_consents as cc')
                ->whereColumn('cc.person_id', 'persons.id')
                ->where('cc.channel', $value)
                ->where('cc.status', 'granted')
                ->whereRaw('cc.id = (select max(c2.id) from contact_consents c2 where c2.person_id = cc.person_id and c2.channel = cc.channel)')),
            'no_contact_days' => $query
                ->whereNotExists(fn ($q) => $q->from('communication_messages')->whereColumn('communication_messages.person_id', 'persons.id')->where('sent_at', '>=', $now->copy()->subDays((int) $value)))
                ->whereNotExists(fn ($q) => $q->from('person_activities')->join('activities', 'activities.id', '=', 'person_activities.activity_id')
                    ->whereColumn('person_activities.person_id', 'persons.id')->where('activities.created_at', '>=', $now->copy()->subDays((int) $value))),
            default => null,
        };
    }

    protected function selectAttribute(Builder $query, string $code, string $option): void
    {
        $query->whereExists(fn ($q) => $q->from('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->join('attribute_options', 'attribute_options.id', '=', 'attribute_values.integer_value')
            ->whereColumn('attribute_values.entity_id', 'persons.id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', $code)
            ->where('attribute_options.name', $option));
    }

    protected function dateAttribute(Builder $query, callable $condition): void
    {
        $query->whereExists(fn ($q) => $condition($q->from('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->whereColumn('attribute_values.entity_id', 'persons.id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'dob')));
    }

    protected function address(Builder $query, callable $condition): void
    {
        $query->whereExists(fn ($q) => $condition($q->from('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->whereColumn('attribute_values.entity_id', 'persons.id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'address')));
    }

    /**
     * State from the mailing address, or from any of the contact's leads.
     */
    protected function addressOrLead(Builder $query, string $key, string $value): void
    {
        $value = strtoupper(trim($value));

        $query->where(fn ($outer) => $outer
            ->whereExists(fn ($q) => $q->from('attribute_values')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->whereColumn('attribute_values.entity_id', 'persons.id')
                ->where('attributes.entity_type', 'persons')
                ->where('attributes.code', 'address')
                ->whereRaw("upper(json_unquote(json_extract(attribute_values.json_value, '$.".$key."'))) = ?", [$value]))
            ->orWhereExists(fn ($q) => $q->from('leads')->whereColumn('leads.person_id', 'persons.id')->where('leads.state_code', $value)));
    }

    protected function household(Builder $query, callable $condition, bool $exists): void
    {
        $sub = fn ($q) => $condition($q->from('lead_household_members')
            ->join('leads', 'leads.id', '=', 'lead_household_members.lead_id')
            ->whereColumn('leads.person_id', 'persons.id'));

        $exists ? $query->whereExists($sub) : $query->whereNotExists($sub);
    }

    protected function policy(Builder $query, callable $condition): void
    {
        $query->whereExists(fn ($q) => $condition($q->from('insurance_policies')->whereColumn('insurance_policies.person_id', 'persons.id')));
    }

    protected function like($value): string
    {
        return addcslashes(trim((string) $value), '%_\\');
    }
}
