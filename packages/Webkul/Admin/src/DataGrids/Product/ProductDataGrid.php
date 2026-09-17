<?php

namespace Webkul\Admin\DataGrids\Product;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProductDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('products')
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.price'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->join('organizations', 'attribute_values.integer_value', '=', 'organizations.id')
                    ->where('attributes.code', 'carrier_id')
                    ->where('attributes.entity_type', 'products')
                    ->whereColumn('attribute_values.entity_id', 'products.id')
                    ->select('organizations.name')
                    ->limit(1),
                'carrier_name'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->join('attribute_options', 'attribute_values.integer_value', '=', 'attribute_options.id')
                    ->where('attributes.code', 'insurance_line')
                    ->where('attributes.entity_type', 'products')
                    ->whereColumn('attribute_values.entity_id', 'products.id')
                    ->select('attribute_options.name')
                    ->limit(1),
                'insurance_line'
            )
            ->selectSub(
                DB::table('attribute_values')
                    ->join('attributes', 'attribute_values.attribute_id', '=', 'attributes.id')
                    ->join('attribute_options', 'attribute_values.integer_value', '=', 'attribute_options.id')
                    ->where('attributes.code', 'metal_tier')
                    ->where('attributes.entity_type', 'products')
                    ->whereColumn('attribute_values.entity_id', 'products.id')
                    ->select('attribute_options.name')
                    ->limit(1),
                'metal_tier'
            );

        $this->addFilter('id', 'products.id');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('name', 'products.name');
        $this->addFilter('price', 'products.price');

        return $queryBuilder;
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'sku',
            'label' => trans('admin::app.products.index.datagrid.sku'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.products.index.datagrid.name'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'carrier_name',
            'label' => trans('admin::app.products.index.datagrid.carrier'),
            'type' => 'string',
            'sortable' => false,
            'searchable' => false,
            'filterable' => false,
            'closure' => fn ($row) => $row->carrier_name ?: '-',
        ]);

        $this->addColumn([
            'index' => 'insurance_line',
            'label' => trans('admin::app.products.index.datagrid.line'),
            'type' => 'string',
            'sortable' => false,
            'searchable' => false,
            'filterable' => false,
            'closure' => fn ($row) => $row->insurance_line ?: '-',
        ]);

        $this->addColumn([
            'index' => 'metal_tier',
            'label' => trans('admin::app.products.index.datagrid.tier'),
            'type' => 'string',
            'sortable' => false,
            'searchable' => false,
            'filterable' => false,
            'closure' => fn ($row) => $row->metal_tier ?: '-',
        ]);

        $this->addColumn([
            'index' => 'price',
            'label' => trans('admin::app.products.index.datagrid.price'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                if ($row->price == 0) {
                    return '<span class="text-green-600 dark:text-green-400 font-semibold">$0.00 / mes</span>';
                }

                return core()->formatBasePrice($row->price, 2).' / mes';
            },
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('products.view')) {
            $this->addAction([
                'index' => 'view',
                'icon' => 'icon-eye',
                'title' => trans('admin::app.products.index.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.products.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('products.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.products.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.products.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('products.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.products.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.products.delete', $row->id),
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
            'title' => trans('admin::app.products.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.products.mass_delete'),
        ]);
    }
}
