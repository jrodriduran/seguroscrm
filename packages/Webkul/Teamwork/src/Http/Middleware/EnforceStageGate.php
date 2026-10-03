<?php

namespace Webkul\Teamwork\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Teamwork\Services\StagePlaybooks;

/**
 * Stops a lead from moving forward (kanban, stage bar or edit form) while
 * required milestones are missing in stages set to "block". The agency
 * owner can still move it from the lead page, giving a reason.
 */
class EnforceStageGate
{
    protected const ROUTES = ['admin.leads.stage.update', 'admin.leads.update'];

    public function __construct(protected StagePlaybooks $playbooks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route || ! in_array($route->getName(), self::ROUTES, true) || ! $request->filled('lead_pipeline_stage_id')) {
            return $next($request);
        }

        $lead = DB::table('leads')->where('id', (int) $route->parameter('id'))->first();
        $target = (int) $request->input('lead_pipeline_stage_id');

        if (! $lead || (int) $lead->lead_pipeline_stage_id === $target) {
            return $next($request);
        }

        $gate = $this->playbooks->gate($lead, $target);

        if ($gate['mode'] !== 'block') {
            return $next($request);
        }

        $message = trans('teamwork::app.playbook.blocked', [
            'items' => collect($gate['missing'])->map(fn ($item) => $item['name'])->unique()->implode(', '),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'missing' => $gate['missing']], 422);
        }

        session()->flash('error', $message);

        return redirect()->back();
    }
}
