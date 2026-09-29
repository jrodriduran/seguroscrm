<?php

namespace Webkul\Teamwork\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\Teamwork\Services\WorkQueue;

class PipelineHealthController extends Controller
{
    public function __construct(
        protected WorkQueue $workQueue,
        protected TeamScope $teamScope,
    ) {}

    /**
     * Dashboard funnel: the selected pipeline's open stages in order, with
     * how many cases sit in each, their value, and how many are late —
     * plus won / lost in the selected period.
     */
    public function index(): JsonResponse
    {
        $user = auth()->guard('user')->user();

        $pipelineId = (int) request('pipeline_id') ?: (int) DB::table('lead_pipelines')->where('is_default', 1)->value('id');

        $pipeline = DB::table('lead_pipelines')->where('id', $pipelineId)->first(['id', 'name']);

        abort_unless($pipeline, 404);

        // Respect "view permission" (global / group / individual) like the rest of the CRM.
        $userIds = bouncer()->getAuthorizedUserIds() ?? $this->teamScope->teamUserIds($user);

        $stages = DB::table('lead_pipeline_stages')
            ->where('lead_pipeline_id', $pipeline->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'probability']);

        $cases = $this->workQueue->openCases($userIds, PHP_INT_MAX)
            ->where('lead_pipeline_id', $pipeline->id)
            ->groupBy('lead_pipeline_stage_id');

        $values = DB::table('leads')
            ->where('lead_pipeline_id', $pipeline->id)
            ->whereIn('user_id', $userIds)
            ->groupBy('lead_pipeline_stage_id')
            ->pluck(DB::raw('sum(coalesce(lead_value, 0))'), 'lead_pipeline_stage_id');

        $showValues = bouncer()->hasPermission('financials');

        $open = $stages->whereNotIn('code', ['won', 'lost'])->values()->map(function ($stage) use ($cases, $values, $showValues) {
            $items = $cases->get($stage->id, collect());

            return [
                'id' => $stage->id,
                'name' => $this->stageName($stage),
                'probability' => (int) $stage->probability,
                'total' => $items->count(),
                'overdue' => $items->where('state', WorkQueue::OVERDUE)->count(),
                'warning' => $items->where('state', WorkQueue::WARNING)->count(),
                'oldest' => $items->first()?->idle_label,
                'value' => $showValues ? (float) ($values[$stage->id] ?? 0) : null,
            ];
        });

        $start = request('start') ? Carbon::parse(request('start'))->startOfDay() : now()->subMonth();
        $end = request('end') ? Carbon::parse(request('end'))->endOfDay() : now();

        $closed = DB::table('leads')
            ->join('lead_pipeline_stages as stages', 'stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->where('leads.lead_pipeline_id', $pipeline->id)
            ->whereIn('leads.user_id', $userIds)
            ->whereIn('stages.code', ['won', 'lost'])
            ->whereBetween(DB::raw('coalesce(leads.closed_at, leads.updated_at)'), [$start, $end])
            ->groupBy('stages.code')
            ->pluck(DB::raw('count(*)'), 'stages.code');

        $won = (int) ($closed['won'] ?? 0);
        $lost = (int) ($closed['lost'] ?? 0);

        return response()->json([
            'pipeline' => $pipeline->name,
            'stages' => $open,
            'totals' => [
                'open' => $open->sum('total'),
                'overdue' => $open->sum('overdue'),
                'warning' => $open->sum('warning'),
                'value' => $showValues ? $open->sum('value') : null,
                'won' => $won,
                'lost' => $lost,
                'win_rate' => $won + $lost ? round($won * 100 / ($won + $lost)) : null,
            ],
            'kanban_url' => route('admin.leads.index', ['pipeline_id' => $pipeline->id]),
            'center_url' => route('admin.teamwork.center', ['tab' => 'team']),
            'currency' => core()->currencySymbol(config('app.currency')),
        ]);
    }

    /**
     * Translated stage name, using the same lookups as the Krayin reports.
     */
    protected function stageName($stage): string
    {
        foreach ([$stage->code, $stage->name] as $source) {
            $slug = Str::slug((string) $source, '_');

            foreach (["admin::insurance.pipeline_stages.general.{$slug}", "admin::app.pipeline_stages.general.{$slug}"] as $key) {
                if ($slug && Lang::has($key)) {
                    return trans($key);
                }
            }
        }

        return (string) $stage->name;
    }
}
