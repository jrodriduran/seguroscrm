<?php

namespace Webkul\Insurance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\AgencyOverrideService;
use Webkul\Teamwork\Services\StagePlaybooks;

class InsuranceQuoteController extends QuoteController
{
    public function convertToPolicy(int $id): JsonResponse|RedirectResponse
    {
        [$quote, $policy] = DB::transaction(function () use ($id) {
            $quote = $this->quoteRepository->getModel()->newQuery()->lockForUpdate()->findOrFail($id);
            $this->preventUnauthorizedAccess($quote->user_id);

            $policy = InsurancePolicy::where('quote_id', $quote->id)->first();
            $commission = InsuranceCommission::where('quote_id', $quote->id)->first();

            // A retry must not reset paid coverage or create another commission.
            if ($quote->quote_status === 'bound' && $policy && $commission) {
                $this->linkCommission($commission, $policy);

                return [$quote, $policy];
            }

            $lead = $quote->leads()->lockForUpdate()->first();
            $wonStage = null;

            if ($lead) {
                $wonStage = Stage::where('lead_pipeline_id', $lead->lead_pipeline_id)
                    ->where('code', 'won')->orderBy('sort_order')->first();

                if (! $wonStage) {
                    throw ValidationException::withMessages([
                        'lead_pipeline_stage_id' => trans('admin::app.insurance-conversion.won-stage-missing'),
                    ]);
                }

                $gate = app(StagePlaybooks::class)->gate($lead, $wonStage->id);

                if ($gate['mode'] === 'block') {
                    throw ValidationException::withMessages([
                        'lead_pipeline_stage_id' => trans('teamwork::app.playbook.blocked', [
                            'items' => collect($gate['missing'])->pluck('name')->unique()->implode(', '),
                        ]),
                    ]);
                }
            }

            $quote->quote_status = 'bound';
            $quote->save();

            $policy ??= InsurancePolicy::createOrUpdateFromQuote($quote);
            $newCommission = ! $commission;
            $commission ??= InsuranceCommission::createFromQuote($quote);
            $this->linkCommission($commission, $policy, $newCommission);
            app(AgencyOverrideService::class)->distributeOverridesForPolicy($policy, $commission);

            if ($lead) {
                $lead->status = 1;
                $lead->lead_pipeline_stage_id = $wonStage->id;
                $lead->lead_value = $quote->net_premium ?? $quote->grand_total;
                $lead->save();
                DB::afterCommit(fn () => Event::dispatch('lead.update.after', $lead));
            }

            return [$quote, $policy];
        });

        $message = trans('admin::app.insurance-conversion.success', ['id' => $quote->id]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'quote' => $quote,
                'policy' => $policy,
            ]);
        }

        session()->flash('success', $message);

        return redirect()->route('admin.quotes.index');
    }

    protected function linkCommission(InsuranceCommission $commission, InsurancePolicy $policy, bool $new = false): void
    {
        if (! $commission->policy_number) {
            $commission->policy_number = $policy->policy_number;
        }

        if ($new) {
            $commission->effective_date = $policy->effective_date;
        }

        if ($commission->isDirty()) {
            $commission->save();
        }
    }
}
