@php
    $aiScoringService = app(\Webkul\Lead\Services\LeadAiScoringService::class);
    $aiEvaluation = $aiScoringService->evaluateLead($lead);
    $score = $aiEvaluation['total_score'];
    $tier = $aiEvaluation['tier'];
    $actions = $aiEvaluation['next_best_actions'];
    $primaryAction = !empty($actions) ? $actions[0] : null;
@endphp

<div class="flex w-full flex-col gap-3 border-b border-gray-300 p-4 dark:border-gray-800 bg-gradient-to-br from-indigo-50/40 via-white to-purple-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950/20">
    <!-- Header with AI Badge & Score -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-lg">🤖</span>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                    @lang('admin::insurance.ai_insights.copilot_title')
                </h4>
                <div class="text-[10px] text-gray-500">@lang('admin::insurance.ai_insights.predictive_priority')</div>
            </div>
        </div>

        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold {{ $score >= 75 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200' : ($score >= 50 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200') }}">
            <span>{{ $score }}/100</span>
            <span class="text-[10px] font-normal">• {{ $tier['label'] }}</span>
        </div>
    </div>

    <!-- Score Progress Bar -->
    <div class="w-full bg-gray-200 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
        <div
            class="h-full rounded-full transition-all duration-500 {{ $score >= 75 ? 'bg-gradient-to-r from-orange-500 to-rose-600' : ($score >= 50 ? 'bg-gradient-to-r from-amber-400 to-amber-600' : 'bg-gradient-to-r from-blue-400 to-indigo-600') }}"
            style="width: {{ $score }}%;"
        ></div>
    </div>

    <!-- Next Best Action (Recommendation) -->
    @if ($primaryAction)
        <div class="p-3 rounded-lg border {{ $primaryAction['priority'] === 'urgent' ? 'border-rose-300 bg-rose-50/40 dark:border-rose-900/60 dark:bg-rose-950/20' : ($primaryAction['priority'] === 'high' ? 'border-amber-300 bg-amber-50/40 dark:border-amber-900/60 dark:bg-amber-950/20' : 'border-blue-200 bg-blue-50/30 dark:border-blue-900/60 dark:bg-blue-950/20') }} space-y-2">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-1.5">
                    <span class="text-sm mt-0.5">{{ $primaryAction['icon'] }}</span>
                    <div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white leading-snug">
                            {{ $primaryAction['title'] }}
                        </div>
                        <div class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 leading-relaxed">
                            {{ $primaryAction['description'] }}
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($primaryAction['tab']))
                <div class="pt-1 flex justify-end">
                    <a
                        href="?tab={{ $primaryAction['tab'] }}"
                        class="text-[11px] font-bold px-2.5 py-1 rounded shadow-sm flex items-center gap-1 transition-colors {{ $primaryAction['priority'] === 'urgent' ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}"
                    >
                        <span>👉</span>
                        {{ $primaryAction['action_label'] }}
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- Score Breakdown Pills -->
    <div class="flex flex-wrap gap-1 pt-1 text-[10px]">
        @if ($aiEvaluation['breakdown']['sep_oep_urgency'] > 0)
            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                +{{ $aiEvaluation['breakdown']['sep_oep_urgency'] }} @lang('admin::insurance.ai_insights.pills_sep')
            </span>
        @endif
        @if ($aiEvaluation['breakdown']['medicare_turning_65'] > 0)
            <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                +25 @lang('admin::insurance.ai_insights.pills_medicare')
            </span>
        @endif
        @if ($aiEvaluation['breakdown']['subsidy_potential'] > 0)
            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                +{{ $aiEvaluation['breakdown']['subsidy_potential'] }} @lang('admin::insurance.ai_insights.pills_subsidy')
            </span>
        @endif
        @if ($aiEvaluation['breakdown']['compliance_readiness'] > 0)
            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300 border border-slate-200 dark:border-gray-700">
                +{{ $aiEvaluation['breakdown']['compliance_readiness'] }} @lang('admin::insurance.ai_insights.pills_compliance')
            </span>
        @endif
    </div>
</div>
