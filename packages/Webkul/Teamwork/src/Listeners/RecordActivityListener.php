<?php

namespace Webkul\Teamwork\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Teamwork\Services\AutomationEngine;
use Webkul\Teamwork\Services\Followers;
use Webkul\Teamwork\Services\Mentions;
use Webkul\Teamwork\Services\Notifier;

/**
 * Turns everyday CRM work into team awareness: when someone logs an
 * activity on a lead or contact, or moves a lead to another stage, the
 * record's followers hear about it, and @mentioned teammates are notified.
 */
class RecordActivityListener
{
    /**
     * Stage of each lead before an update, keyed by lead id.
     */
    protected static array $stagesBefore = [];

    public function __construct(
        protected Followers $followers,
        protected Mentions $mentions,
        protected Notifier $notifier,
    ) {}

    public function activityCreated($activity): void
    {
        if (! $activity || ! ($user = auth()->guard('user')->user()) || $activity->type === 'system') {
            return;
        }

        $label = trans('teamwork::app.activity-types.'.$activity->type);
        $body = Str::limit(trim(strip_tags((string) ($activity->title ?: $activity->comment))), 200);

        foreach (DB::table('lead_activities')->where('activity_id', $activity->id)->pluck('lead_id') as $leadId) {
            $title = (string) DB::table('leads')->where('id', $leadId)->value('title');
            $url = route('admin.leads.view', $leadId, false);

            $mentioned = $this->mentions->extract((string) $activity->comment.' '.$activity->title);

            $this->notifier->notify($mentioned, Notifier::MENTION, trans('teamwork::app.notifications.mention', ['name' => $user->name, 'record' => $title]), $body, $url);

            $this->followers->notify('lead', (int) $leadId, Notifier::RECORD_ACTIVITY,
                trans('teamwork::app.notifications.activity', ['name' => $user->name, 'type' => $label, 'record' => $title]),
                $body, $url, $mentioned);
        }

        foreach (DB::table('person_activities')->where('activity_id', $activity->id)->pluck('person_id') as $personId) {
            $title = (string) DB::table('persons')->where('id', $personId)->value('name');

            $this->followers->notify('person', (int) $personId, Notifier::RECORD_ACTIVITY,
                trans('teamwork::app.notifications.activity', ['name' => $user->name, 'type' => $label, 'record' => $title]),
                $body, route('admin.contacts.persons.view', $personId, false));
        }
    }

    public function leadUpdating($leadId): void
    {
        if (is_numeric($leadId)) {
            static::$stagesBefore[(int) $leadId] = DB::table('leads')->where('id', $leadId)->value('lead_pipeline_stage_id');
        }
    }

    public function leadUpdated($lead): void
    {
        if (! $lead || ! ($user = auth()->guard('user')->user()) || ! array_key_exists($lead->id, static::$stagesBefore)) {
            return;
        }

        $before = static::$stagesBefore[$lead->id];

        unset(static::$stagesBefore[$lead->id]);

        if ((int) $before === (int) $lead->lead_pipeline_stage_id) {
            return;
        }

        $stage = (string) DB::table('lead_pipeline_stages')->where('id', $lead->lead_pipeline_stage_id)->value('name');

        $this->followers->notify('lead', (int) $lead->id, Notifier::RECORD_STAGE,
            trans('teamwork::app.notifications.stage', ['name' => $user->name, 'record' => $lead->title, 'stage' => $stage]),
            null, route('admin.leads.view', $lead->id, false));

        app(AutomationEngine::class)->stageEntered($lead);
    }

    public function leadCreated($lead): void
    {
        if ($lead && isset($lead->id)) {
            app(AutomationEngine::class)->leadCreated($lead);
        }
    }
}
