<?php

namespace Webkul\Insurance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Lead\Models\Lead;

class AgentIdentity
{
    /** Configured identity is not a verification of licensing or carrier appointments. */
    public function forLead(Lead $lead): array
    {
        $agent = $lead->user;
        $profile = $agent ? DB::table('insurance_agent_identities')->where('user_id', $agent->id)->first() : null;

        if (! $agent || ! $agent->status || ! trim($agent->name) || ! $profile
            || ! preg_match('/^[1-9][0-9]{0,9}$/', $profile->npn) || ! trim($profile->agency_name)) {
            throw ValidationException::withMessages(['agent_identity' => trans('admin::app.insurance-identity.required')]);
        }

        return ['user_id' => $agent->id, 'agent_name' => $agent->name,
            'agent_npn' => $profile->npn, 'agency_name' => $profile->agency_name];
    }
}
