<?php

namespace Webkul\Admin\DataGrids\Lead;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\User\Repositories\UserRepository;

class TeamRadarDataGrid extends DataGrid
{
    /**
     * Create a new DataGrid instance.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Prepare query builder — one row per agent, with SLA aggregates.
     */
    public function prepareQueryBuilder(): Builder
    {
        $today = now()->toDateString();

        $queryBuilder = DB::table('users')
            ->select([
                'users.id as user_id',
                'users.name as agent_name',
                'users.email as agent_email',
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN DATE(leads.assigned_at) = '{$today}'
                        THEN leads.id END
                    ) AS assigned_today
                "),
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN leads.sla_status = 'active'
                        AND DATE(leads.assigned_at) = '{$today}'
                        THEN leads.id END
                    ) AS on_time
                "),
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN leads.sla_status = 'overdue'
                        THEN leads.id END
                    ) AS overdue_count
                "),
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN leads.sla_status = 'escalated'
                        THEN leads.id END
                    ) AS escalated_count
                "),
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN leads.sla_status = 'pending'
                        THEN leads.id END
                    ) AS pending_contact
                "),
                DB::raw("
                    COUNT(DISTINCT CASE
                        WHEN lead_pipeline_stages.code = 'won'
                        AND DATE(leads.closed_at) = '{$today}'
                        THEN leads.id END
                    ) AS policies_issued_today
                "),
            ])
            ->leftJoin('leads', 'leads.user_id', '=', 'users.id')
            ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
            ->where('users.status', 1)
            ->groupBy('users.id', 'users.name', 'users.email');

        $this->addFilter('agent_name', 'users.name');

        return $queryBuilder;
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'agent_name',
            'label' => trans('admin::insurance.team_radar.agent'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'assigned_today',
            'label' => trans('admin::insurance.team_radar.assigned_today'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->assigned_today ?? 0,
        ]);

        $this->addColumn([
            'index' => 'on_time',
            'label' => trans('admin::insurance.team_radar.on_time'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->on_time ?? 0,
        ]);

        $this->addColumn([
            'index' => 'overdue_count',
            'label' => trans('admin::insurance.team_radar.overdue'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->overdue_count ?? 0,
        ]);

        $this->addColumn([
            'index' => 'escalated_count',
            'label' => trans('admin::insurance.team_radar.escalated'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->escalated_count ?? 0,
        ]);

        $this->addColumn([
            'index' => 'pending_contact',
            'label' => trans('admin::insurance.team_radar.pending_contact'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->pending_contact ?? 0,
        ]);

        $this->addColumn([
            'index' => 'policies_issued_today',
            'label' => trans('admin::insurance.team_radar.policies_issued'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => fn ($row) => $row->policies_issued_today ?? 0,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        // No row-level actions; bulk reassign is handled via a dedicated endpoint.
    }
}
