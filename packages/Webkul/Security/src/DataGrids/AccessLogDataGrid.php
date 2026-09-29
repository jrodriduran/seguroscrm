<?php

namespace Webkul\Security\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\Security\Models\AccessLog;

class AccessLogDataGrid extends DataGrid
{
    /**
     * Newest first.
     */
    protected $sortColumn = 'id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('user_access_logs')
            ->addSelect(
                'user_access_logs.id',
                'user_access_logs.created_at',
                'user_access_logs.email',
                'user_access_logs.event',
                'user_access_logs.ip_address',
                'user_access_logs.user_agent',
                'users.name as user_name',
            )
            ->leftJoin('users', 'user_access_logs.user_id', '=', 'users.id');

        // Agency admins only see their own team.
        if ($agencyId = auth()->guard('user')->user()->agency_id) {
            $queryBuilder->where('users.agency_id', $agencyId);
        }

        $this->addFilter('id', 'user_access_logs.id');
        $this->addFilter('created_at', 'user_access_logs.created_at');
        $this->addFilter('email', 'user_access_logs.email');
        $this->addFilter('event', 'user_access_logs.event');
        $this->addFilter('ip_address', 'user_access_logs.ip_address');
        $this->addFilter('user_name', 'users.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('security::app.access-logs.datagrid.date'),
            'type' => 'date',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'closure' => fn ($row) => core()->formatDate($row->created_at, 'd M Y H:i:s'),
        ]);

        $this->addColumn([
            'index' => 'user_name',
            'label' => trans('security::app.access-logs.datagrid.user'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => e($row->user_name ?? '—'),
        ]);

        $this->addColumn([
            'index' => 'email',
            'label' => trans('security::app.access-logs.datagrid.email'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'event',
            'label' => trans('security::app.access-logs.datagrid.event'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => collect(AccessLog::EVENTS)->map(fn ($event) => [
                'label' => trans('security::app.access-logs.events.'.$event),
                'value' => $event,
            ])->all(),
            'closure' => fn ($row) => $this->badge($row->event),
        ]);

        $this->addColumn([
            'index' => 'ip_address',
            'label' => trans('security::app.access-logs.datagrid.ip'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'user_agent',
            'label' => trans('security::app.access-logs.datagrid.device'),
            'type' => 'string',
            'closure' => fn ($row) => '<span title="'.e($row->user_agent).'">'.e($this->device((string) $row->user_agent)).'</span>',
        ]);
    }

    /**
     * Coloured pill for the event type.
     */
    protected function badge(string $event): string
    {
        // Inline colours: these utility shades are not all in the compiled Tailwind build.
        $color = [
            AccessLog::EVENT_LOGIN => '#059669',
            AccessLog::EVENT_LOGOUT => '#64748b',
            AccessLog::EVENT_FAILED => '#d97706',
            AccessLog::EVENT_BLOCKED_IP => '#dc2626',
            AccessLog::EVENT_MFA_FAILED => '#d97706',
            AccessLog::EVENT_MFA_ENABLED => '#0284c7',
            AccessLog::EVENT_MFA_DISABLED => '#ea580c',
            AccessLog::EVENT_MFA_RESET => '#7c3aed',
        ][$event] ?? '#64748b';

        return '<span style="color: '.$color.'; background: color-mix(in srgb, '.$color.' 12%, transparent); border: 1px solid color-mix(in srgb, '.$color.' 25%, transparent);" class="rounded-md px-2 py-1 text-xs font-semibold">'
            .e(trans('security::app.access-logs.events.'.$event))
            .'</span>';
    }

    /**
     * Short "Browser · OS" label from a user agent string.
     */
    protected function device(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => '',
        };

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => '',
        };

        return trim($browser.($browser && $os ? ' · ' : '').$os) ?: '—';
    }
}
