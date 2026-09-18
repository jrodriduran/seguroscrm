<?php

namespace Webkul\Admin\DataGrids\Quote;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\DataGrid\DataGrid;
use Webkul\User\Repositories\UserRepository;

class QuoteDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('quotes')
            ->addSelect(
                'quotes.id',
                'quotes.subject',
                'quotes.carrier_name',
                'quotes.plan_name',
                'quotes.metal_tier',
                'quotes.gross_premium',
                'quotes.aptc_subsidy',
                'quotes.net_premium',
                'quotes.quote_status',
                'quotes.expired_at',
                'quotes.sub_total',
                'quotes.discount_amount',
                'quotes.tax_amount',
                'quotes.adjustment_amount',
                'quotes.grand_total',
                'quotes.created_at',
                'users.id as user_id',
                'users.name as sales_person',
                'persons.id as person_id',
                'persons.name as person_name',
                'quotes.expired_at as expired_quotes'
            )
            ->leftJoin('users', 'quotes.user_id', '=', 'users.id')
            ->leftJoin('persons', 'quotes.person_id', '=', 'persons.id');

        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->whereIn('quotes.user_id', $userIds);
        }

        $this->addFilter('id', 'quotes.id');
        $this->addFilter('user', 'quotes.user_id');
        $this->addFilter('sales_person', 'users.name');
        $this->addFilter('person_name', 'persons.name');
        $this->addFilter('carrier_name', 'quotes.carrier_name');
        $this->addFilter('plan_name', 'quotes.plan_name');
        $this->addFilter('quote_status', 'quotes.quote_status');
        $this->addFilter('expired_at', 'quotes.expired_at');
        $this->addFilter('created_at', 'quotes.created_at');

        if (request()->input('expired_quotes.in') == 1) {
            $this->addFilter('expired_quotes', DB::raw('DATEDIFF(NOW(), '.$tablePrefix.'quotes.expired_at) >= '.$tablePrefix.'NOW()'));
        } else {
            $this->addFilter('expired_quotes', DB::raw('DATEDIFF(NOW(), '.$tablePrefix.'quotes.expired_at) < '.$tablePrefix.'NOW()'));
        }

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'subject',
            'label' => 'Propuesta / Plan Médico',
            'type' => 'string',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->carrier_name) {
                    $tierColors = [
                        'bronze' => 'background-color:#92400e; color:#ffffff;',
                        'silver' => 'background-color:#475569; color:#ffffff;',
                        'gold' => 'background-color:#ca8a04; color:#ffffff;',
                        'platinum' => 'background-color:#4f46e5; color:#ffffff;',
                        'catastrophic' => 'background-color:#dc2626; color:#ffffff;',
                    ];
                    $tierStyle = $tierColors[strtolower($row->metal_tier ?? '')] ?? 'background-color:#2563eb; color:#ffffff;';
                    $tierBadge = $row->metal_tier
                        ? "<span style='padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; {$tierStyle}'>".strtoupper($row->metal_tier).'</span>'
                        : '';

                    $editUrl = route('admin.quotes.edit', $row->id);

                    return "<div class='flex flex-col gap-0.5'>"
                        ."<div class='flex items-center gap-1.5 font-bold text-gray-900 dark:text-white'>"
                        ."<a href='{$editUrl}' class='hover:underline text-blue-600 dark:text-blue-400'>{$row->carrier_name}</a>"
                        .$tierBadge
                        .'</div>'
                        ."<div class='text-xs text-gray-500 font-medium'>".e($row->plan_name ?: $row->subject).'</div>'
                        .'</div>';
                }

                $editUrl = route('admin.quotes.edit', $row->id);

                return "<a href='{$editUrl}' class='font-semibold text-blue-600 dark:text-blue-400 hover:underline'>".e($row->subject).'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'person_name',
            'label' => trans('admin::app.quotes.index.datagrid.person'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => PersonRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
            'closure' => function ($row) {
                $route = route('admin.contacts.persons.view', $row->person_id);

                return "<a class=\"text-brandColor font-medium transition-all hover:underline\" href='".$route."'>".$row->person_name.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'sales_person',
            'label' => trans('admin::app.quotes.index.datagrid.sales-person'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => UserRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
        ]);

        $this->addColumn([
            'index' => 'gross_premium',
            'label' => 'Prima Real',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->gross_premium ?? $row->sub_total ?? 0, 2),
        ]);

        $this->addColumn([
            'index' => 'aptc_subsidy',
            'label' => 'Subsidio APTC',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $val = (float) ($row->aptc_subsidy ?? $row->discount_amount ?? 0);

                return $val > 0
                    ? "<span class='font-bold text-emerald-600 dark:text-emerald-400'>-".core()->formatBasePrice($val, 2).'</span>'
                    : core()->formatBasePrice(0, 2);
            },
        ]);

        $this->addColumn([
            'index' => 'net_premium',
            'label' => 'Pago Cliente',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $val = (float) ($row->net_premium ?? $row->grand_total ?? 0);

                return "<span class='font-extrabold text-sm text-blue-600 dark:text-blue-400'>".core()->formatBasePrice($val, 2)."<span class='text-xs font-normal text-gray-500'>/mes</span></span>";
            },
        ]);

        $this->addColumn([
            'index' => 'quote_status',
            'label' => 'Estado',
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $status = $row->quote_status ?? 'draft';
                $badgeClasses = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                    'presented' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                    'accepted' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                    'bound' => 'bg-blue-600 text-white font-bold',
                    'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200',
                ];
                $labels = [
                    'draft' => 'Borrador',
                    'presented' => 'Presentada',
                    'accepted' => 'Aceptada',
                    'bound' => 'Emitida / Póliza',
                    'rejected' => 'Rechazada',
                ];
                $cls = $badgeClasses[$status] ?? 'bg-gray-100 text-gray-800';
                $label = $labels[$status] ?? ucfirst($status);

                return "<span class='px-2.5 py-0.5 text-xs rounded-full font-semibold {$cls}'>{$label}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.quotes.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('quotes.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.quotes.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.quotes.edit', $row->id),
            ]);

            $this->addAction([
                'index' => 'convert_to_policy',
                'icon' => 'icon-tick',
                'title' => 'Emitir Póliza (Convertir Cotización)',
                'method' => 'POST',
                'url' => fn ($row) => route('admin.quotes.convert_to_policy', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('quotes.print')) {
            $this->addAction([
                'index' => 'print',
                'icon' => 'icon-print',
                'title' => trans('admin::app.quotes.index.datagrid.print'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.quotes.print', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('quotes.mail')) {
            $this->addAction([
                'index' => 'mail',
                'icon' => 'icon-mail',
                'title' => trans('admin::app.quotes.index.datagrid.mail'),
                'method' => 'POST',
                'url' => fn ($row) => route('admin.leads.quotes.mail', ['quote_id' => $row->id]),
            ]);
        }

        if (bouncer()->hasPermission('quotes.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.quotes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.quotes.delete', $row->id),
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
            'title' => trans('admin::app.quotes.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.quotes.mass_delete'),
        ]);

        $this->addMassAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.quotes.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.quotes.mass_delete'),
        ]);
    }
}
