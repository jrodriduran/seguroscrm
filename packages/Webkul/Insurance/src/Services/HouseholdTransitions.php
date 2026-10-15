<?php

namespace Webkul\Insurance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Contact\Models\Person;
use Webkul\Insurance\Models\HouseholdTransition;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Stage;
use Webkul\Teamwork\Models\FollowUp;

class HouseholdTransitions
{
    public function plan(Lead $lead, int $memberId, array $data, int $actor): HouseholdTransition
    {
        return DB::transaction(function () use ($lead, $memberId, $data, $actor) {
            $member = $lead->householdMembers()->lockForUpdate()->findOrFail($memberId);

            if (HouseholdTransition::where('household_member_id', $member->id)->where('status', 'planned')->exists()) {
                throw ValidationException::withMessages(['member_id' => trans('admin::app.family-workflow.duplicate')]);
            }

            $transition = HouseholdTransition::create($data + [
                'lead_id' => $lead->id, 'household_member_id' => $member->id,
                'created_by' => $actor, 'status' => 'planned',
            ]);
            $owner = $lead->user_id ?: $actor;
            FollowUp::create([
                'agency_id' => $lead->agency_id, 'entity_type' => 'lead', 'entity_id' => $lead->id,
                'source' => 'household-transition:'.$transition->id,
                'title' => trans('admin::app.family-workflow.task'),
                'note' => trans('admin::app.family-workflow.task-note'),
                'url' => route('admin.leads.view', $lead->id, false),
                'assigned_to' => $owner, 'created_by' => $actor, 'priority' => 'normal',
                'status' => 'open', 'due_at' => $data['review_on'].' 09:00:00', 'last_activity_at' => now(),
            ]);

            return $transition;
        });
    }

    public function resolve(Lead $lead, int $id, string $status, string $reference, int $actor): HouseholdTransition
    {
        return DB::transaction(function () use ($lead, $id, $status, $reference, $actor) {
            $item = HouseholdTransition::where('lead_id', $lead->id)->lockForUpdate()->findOrFail($id);

            if ($item->status !== 'planned') {
                if ($item->status === $status && $item->confirmation_reference === $reference) {
                    return $item;
                }

                throw ValidationException::withMessages(['status' => trans('admin::app.family-workflow.resolved')]);
            }

            $item->update(['status' => $status, 'confirmation_reference' => $reference, 'resolved_by' => $actor, 'resolved_at' => now()]);
            FollowUp::where('source', 'household-transition:'.$item->id)->where('status', 'open')->update([
                'status' => 'done', 'resolved_by' => $actor, 'resolved_at' => now(),
            ]);

            // A local confirmation is a record of agent review, never an external cancellation.
            // Tax household, member data, policy and commission records remain untouched.
            return $item->fresh();
        });
    }

    public function individualCase(Lead $lead, int $id, int $actor): HouseholdTransition
    {
        return DB::transaction(function () use ($lead, $id, $actor) {
            $item = HouseholdTransition::where('lead_id', $lead->id)->lockForUpdate()->findOrFail($id);

            if ($item->status === 'cancelled' || $item->reason === 'death') {
                throw ValidationException::withMessages(['status' => trans('admin::app.family-workflow.resolved')]);
            }

            if ($item->individual_lead_id) {
                return $item;
            }

            $member = $lead->householdMembers()->lockForUpdate()->findOrFail($item->household_member_id);
            $existing = HouseholdTransition::where('household_member_id', $member->id)->whereNotNull('individual_lead_id')->first();
            if ($existing) {
                $item->update(['individual_lead_id' => $existing->individual_lead_id]);

                return $item->fresh();
            }
            $stage = Stage::where('lead_pipeline_id', $lead->lead_pipeline_id)
                ->whereNotIn('code', ['won', 'lost'])->orderBy('sort_order')->firstOrFail();
            $person = Person::create(['name' => $member->name, 'user_id' => $lead->user_id ?: $actor, 'emails' => [], 'contact_numbers' => []]);
            $newLead = Lead::create([
                'title' => trans('admin::app.family-workflow.individual-title'),
                'person_id' => $person->id, 'user_id' => $lead->user_id ?: $actor, 'agency_id' => $lead->agency_id,
                'lead_pipeline_id' => $lead->lead_pipeline_id, 'lead_pipeline_stage_id' => $stage->id,
                'status' => 1,
            ]);
            // Do not copy parental contact details, SSN, household income or consent.
            $item->update(['individual_lead_id' => $newLead->id]);
            FollowUp::create([
                'agency_id' => $lead->agency_id, 'entity_type' => 'lead', 'entity_id' => $newLead->id,
                'source' => 'household-individual:'.$member->id,
                'title' => trans('admin::app.family-workflow.individual-title'),
                'note' => trans('admin::app.family-workflow.task-note'),
                'url' => route('admin.leads.view', $newLead->id, false),
                'assigned_to' => $newLead->user_id, 'created_by' => $actor, 'priority' => 'normal',
                'status' => 'open', 'due_at' => $item->review_on->copy()->setTime(9, 0), 'last_activity_at' => now(),
            ]);

            return $item->fresh();
        });
    }

    public function reviewAges(): int
    {
        $count = 0;
        HouseholdMember::where('relationship', 'child')->where('is_applying_coverage', true)
            ->whereNotNull('date_of_birth')->chunkById(100, function ($members) use (&$count) {
                foreach ($members as $member) {
                    $birthday = $member->date_of_birth->copy()->addYearsNoOverflow(26);
                    if ($birthday->year < now()->year || $birthday->isAfter(today()->addDays(90))) {
                        continue;
                    }
                    $lead = Lead::find($member->lead_id);
                    if (! $lead?->user_id) {
                        continue;
                    }
                    // Serialize on member so repeated/concurrent runs cannot duplicate the task.
                    $count += DB::transaction(function () use ($member, $lead, $birthday) {
                        HouseholdMember::whereKey($member->id)->lockForUpdate()->firstOrFail();
                        $source = 'household-age26:'.$member->id;
                        if (FollowUp::where('source', $source)->exists()) {
                            return 0;
                        }
                        FollowUp::create([
                            'agency_id' => $lead->agency_id, 'entity_type' => 'lead', 'entity_id' => $lead->id,
                            'source' => $source, 'title' => trans('admin::app.family-workflow.age-task'),
                            'note' => trans('admin::app.family-workflow.age-note', ['date' => $birthday->toDateString()]),
                            'url' => route('admin.leads.view', $lead->id, false),
                            'assigned_to' => $lead->user_id, 'created_by' => $lead->user_id,
                            'priority' => 'normal', 'status' => 'open', 'due_at' => today()->setTime(9, 0), 'last_activity_at' => now(),
                        ]);

                        return 1;
                    });
                }
            });

        return $count;
    }
}
