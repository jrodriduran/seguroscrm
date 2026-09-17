<?php

namespace Webkul\Admin\DataGrids\Contact;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class OrganizationDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('organizations')
            ->addSelect(
                'organizations.id',
                'organizations.name',
                'organizations.address',
                'organizations.created_at'
            )
            ->selectSub(
                DB::table('persons')
                    ->selectRaw('count(*)')
                    ->whereColumn('persons.organization_id', 'organizations.id'),
                'persons_count'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->where('attributes.code', 'carrier_code')
                    ->where('attributes.entity_type', 'organizations')
                    ->whereColumn('attribute_values.entity_id', 'organizations.id')
                    ->select('attribute_values.text_value')
                    ->limit(1),
                'carrier_code'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->where('attributes.code', 'agent_support_phone')
                    ->where('attributes.entity_type', 'organizations')
                    ->whereColumn('attribute_values.entity_id', 'organizations.id')
                    ->select('attribute_values.text_value')
                    ->limit(1),
                'agent_support_phone'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->where('attributes.code', 'broker_portal_url')
                    ->where('attributes.entity_type', 'organizations')
                    ->whereColumn('attribute_values.entity_id', 'organizations.id')
                    ->select('attribute_values.text_value')
                    ->limit(1),
                'broker_portal_url'
            );

        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->whereIn('organizations.user_id', $userIds);
        }

        $this->addFilter('id', 'organizations.id');
        $this->addFilter('name', 'organizations.name');
        $this->addFilter('organization', 'organizations.name');

        return $queryBuilder;
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'carrier_code',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.carrier_code'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->carrier_code ?: '-',
        ]);

        $this->addColumn([
            'index' => 'agent_support_phone',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.support_phone'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => false,
            'filterable' => false,
            'closure' => fn ($row) => $row->agent_support_phone ?: '-',
        ]);

        $this->addColumn([
            'index' => 'broker_portal_url',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.broker_portal'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => false,
            'filterable' => false,
            'closure' => function ($row) {
                if (! empty($row->broker_portal_url)) {
                    $url = $row->broker_portal_url;
                    $label = trans('admin::app.contacts.organizations.index.datagrid.open_portal');

                    return '<a href="'.e($url)."\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-brandColor hover:underline font-semibold inline-flex items-center gap-1\">🌐 {$label}</a>";
                }

                return '-';
            },
        ]);

        $this->addColumn([
            'index' => 'persons_count',
            'label' => trans('admin::app.contacts.organizations.index.datagrid.persons-count'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => false,
            'filterable' => false,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.settings.tags.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('contacts.organizations.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => trans('admin::app.contacts.organizations.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.contacts.organizations.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('contacts.organizations.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.contacts.organizations.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.contacts.organizations.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.contacts.organizations.index.datagrid.delete'),
            'method' => 'PUT',
            'url' => route('admin.contacts.organizations.mass_delete'),
        ]);
    }
}
