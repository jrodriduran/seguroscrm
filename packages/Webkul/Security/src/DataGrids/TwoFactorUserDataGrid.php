<?php

namespace Webkul\Security\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class TwoFactorUserDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('users')
            ->addSelect(
                'users.id',
                'users.name',
                'users.email',
                'roles.name as role_name',
                'user_two_factor.confirmed_at as two_factor_since',
                DB::raw('(select count(*) from user_trusted_devices d where d.user_id = users.id and d.expires_at > now()) as trusted_devices'),
            )
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('user_two_factor', function ($join) {
                $join->on('user_two_factor.user_id', '=', 'users.id')->whereNotNull('user_two_factor.confirmed_at');
            });

        // Agency admins only see their own team.
        if ($agencyId = auth()->guard('user')->user()->agency_id) {
            $queryBuilder->where('users.agency_id', $agencyId);
        }

        $this->addFilter('id', 'users.id');
        $this->addFilter('name', 'users.name');
        $this->addFilter('email', 'users.email');
        $this->addFilter('role_name', 'roles.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'name',
            'label' => trans('security::app.two-factor.users.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'email',
            'label' => trans('security::app.two-factor.users.datagrid.email'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'role_name',
            'label' => trans('security::app.two-factor.users.datagrid.role'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'two_factor_since',
            'label' => trans('security::app.two-factor.users.datagrid.status'),
            'type' => 'string',
            'sortable' => true,
            'closure' => fn ($row) => $row->two_factor_since
                ? '<span style="color: #059669; background: rgba(5, 150, 105, 0.1);" class="rounded-md px-2 py-1 text-xs font-semibold">'.e(trans('security::app.two-factor.users.enabled-since', ['date' => core()->formatDate($row->two_factor_since, 'd M Y')])).'</span>'
                : '<span style="color: #64748b; background: rgba(100, 116, 139, 0.1);" class="rounded-md px-2 py-1 text-xs font-semibold">'.e(trans('security::app.two-factor.users.disabled')).'</span>',
        ]);

        $this->addColumn([
            'index' => 'trusted_devices',
            'label' => trans('security::app.two-factor.users.datagrid.devices'),
            'type' => 'integer',
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('settings.security.two_factor.reset')) {
            $this->addAction([
                'index' => 'reset',
                'icon' => 'icon-delete',
                'title' => trans('security::app.two-factor.users.reset'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.settings.security.two_factor.reset', $row->id),
            ]);
        }
    }
}
