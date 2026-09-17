<?php

namespace Webkul\Admin\DataGrids\Lead;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeValue;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Contract\Repositories\Pipeline;
use Webkul\DataGrid\DataGrid;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\Tag\Repositories\TagRepository;
use Webkul\User\Repositories\UserRepository;

class LeadDataGrid extends DataGrid
{
    /**
     * Pipeline instance.
     *
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * Create data grid instance.
     *
     * @return void
     */
    public function __construct(
        protected PipelineRepository $pipelineRepository,
        protected StageRepository $stageRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
        protected UserRepository $userRepository,
        protected TagRepository $tagRepository,
    ) {
        if (request('pipeline_id')) {
            $this->pipeline = $this->pipelineRepository->find(request('pipeline_id'));
        } else {
            $this->pipeline = $this->pipelineRepository->getDefaultPipeline();
        }
    }

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('leads')
            ->addSelect(
                'leads.id',
                'leads.title',
                'leads.status',
                'leads.lead_value',
                'leads.expected_close_date',
                'lead_sources.name as lead_source_name',
                'lead_types.name as lead_type_name',
                'leads.created_at',
                'lead_pipeline_stages.name as stage',
                'lead_tags.tag_id as tag_id',
                'users.id as user_id',
                'users.name as sales_person',
                'persons.id as person_id',
                'persons.name as person_name',
                'tags.name as tag_name',
                'lead_pipelines.rotten_days as pipeline_rotten_days',
                'lead_pipeline_stages.code as stage_code',
                DB::raw('CASE WHEN DATEDIFF(NOW(),'.$tablePrefix.'leads.created_at) >='.$tablePrefix.'lead_pipelines.rotten_days THEN 1 ELSE 0 END as rotten_lead'),
            )
            ->leftJoin('users', 'leads.user_id', '=', 'users.id')
            ->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
            ->leftJoin('lead_types', 'leads.lead_type_id', '=', 'lead_types.id')
            ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->leftJoin('lead_pipelines', 'leads.lead_pipeline_id', '=', 'lead_pipelines.id')
            ->leftJoin('lead_tags', 'leads.id', '=', 'lead_tags.lead_id')
            ->leftJoin('tags', 'tags.id', '=', 'lead_tags.tag_id')
            ->groupBy('leads.id')
            ->where('leads.lead_pipeline_id', $this->pipeline->id);

        /**
         * Custom (user defined) attribute values live in the EAV `attribute_values` table, so they
         * are pulled in as correlated sub-selects only when exporting. They are hidden from the
         * grid display (see prepareColumns) but included in the exported file.
         */
        if (request()->boolean('export')) {
            foreach ($this->getCustomAttributes() as $attribute) {
                $valueColumn = AttributeValue::$attributeTypeFields[$attribute->type] ?? 'text_value';

                $queryBuilder->addSelect(DB::raw(
                    '(SELECT '.$tablePrefix.'attribute_values.'.$valueColumn.
                    ' FROM '.$tablePrefix.'attribute_values'.
                    ' WHERE '.$tablePrefix.'attribute_values.entity_id = '.$tablePrefix.'leads.id'.
                    ' AND '.$tablePrefix.'attribute_values.attribute_id = '.(int) $attribute->id.
                    ' AND '.$tablePrefix."attribute_values.entity_type = 'leads'".
                    ' LIMIT 1) as '.$attribute->code
                ));
            }
        }

        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->whereIn('leads.user_id', $userIds);
        }

        if (! is_null(request()->input('rotten_lead.in'))) {
            $queryBuilder->havingRaw($tablePrefix.'rotten_lead = ?', [
                (int) request()->input('rotten_lead.in'),
            ]);
        }

        // Policy & Book of Business sub-queries
        $policyAttrMap = [
            'policy_number'  => 'text_value',
            'effective_date' => 'date_value',
            'renewal_date'   => 'date_value',
            'issued_premium' => 'float_value',
        ];

        foreach ($policyAttrMap as $code => $col) {
            $queryBuilder->addSelect(DB::raw(
                '(SELECT '.$tablePrefix.'attribute_values.'.$col.
                ' FROM '.$tablePrefix.'attribute_values'.
                ' INNER JOIN '.$tablePrefix.'attributes ON '.$tablePrefix.'attribute_values.attribute_id = '.$tablePrefix.'attributes.id'.
                ' WHERE '.$tablePrefix.'attribute_values.entity_id = '.$tablePrefix.'leads.id'.
                ' AND '.$tablePrefix.'attributes.code = \''.$code.'\''.
                ' AND '.$tablePrefix.'attribute_values.entity_type = \'leads\' LIMIT 1) as '.$code
            ));

            $this->addFilter($code, DB::raw(
                '(SELECT '.$tablePrefix.'attribute_values.'.$col.
                ' FROM '.$tablePrefix.'attribute_values'.
                ' INNER JOIN '.$tablePrefix.'attributes ON '.$tablePrefix.'attribute_values.attribute_id = '.$tablePrefix.'attributes.id'.
                ' WHERE '.$tablePrefix.'attribute_values.entity_id = '.$tablePrefix.'leads.id'.
                ' AND '.$tablePrefix.'attributes.code = \''.$code.'\''.
                ' AND '.$tablePrefix.'attribute_values.entity_type = \'leads\' LIMIT 1)'
            ));
        }

        $queryBuilder->addSelect(DB::raw(
            '(SELECT '.$tablePrefix.'attribute_options.name'.
            ' FROM '.$tablePrefix.'attribute_values'.
            ' INNER JOIN '.$tablePrefix.'attributes ON '.$tablePrefix.'attribute_values.attribute_id = '.$tablePrefix.'attributes.id'.
            ' LEFT JOIN '.$tablePrefix.'attribute_options ON '.$tablePrefix.'attribute_values.integer_value = '.$tablePrefix.'attribute_options.id'.
            ' WHERE '.$tablePrefix.'attribute_values.entity_id = '.$tablePrefix.'leads.id'.
            ' AND '.$tablePrefix.'attributes.code = \'policy_status\''.
            ' AND '.$tablePrefix.'attribute_values.entity_type = \'leads\' LIMIT 1) as policy_status'
        ));

        $this->addFilter('policy_status', DB::raw(
            '(SELECT '.$tablePrefix.'attribute_options.name'.
            ' FROM '.$tablePrefix.'attribute_values'.
            ' INNER JOIN '.$tablePrefix.'attributes ON '.$tablePrefix.'attribute_values.attribute_id = '.$tablePrefix.'attributes.id'.
            ' LEFT JOIN '.$tablePrefix.'attribute_options ON '.$tablePrefix.'attribute_values.integer_value = '.$tablePrefix.'attribute_options.id'.
            ' WHERE '.$tablePrefix.'attribute_values.entity_id = '.$tablePrefix.'leads.id'.
            ' AND '.$tablePrefix.'attributes.code = \'policy_status\''.
            ' AND '.$tablePrefix.'attribute_values.entity_type = \'leads\' LIMIT 1)'
        ));

        $this->addFilter('id', 'leads.id');
        $this->addFilter('user', 'leads.user_id');
        $this->addFilter('sales_person', 'users.name');
        $this->addFilter('lead_source_name', 'lead_sources.id');
        $this->addFilter('lead_type_name', 'lead_types.id');
        $this->addFilter('person_name', 'persons.name');
        $this->addFilter('type', 'lead_pipeline_stages.code');
        $this->addFilter('stage', 'lead_pipeline_stages.id');
        $this->addFilter('tag_name', 'tags.name');
        $this->addFilter('expected_close_date', 'leads.expected_close_date');
        $this->addFilter('created_at', 'leads.created_at');
        $this->addFilter('rotten_lead', DB::raw('DATEDIFF(NOW(), '.$tablePrefix.'leads.created_at) >= '.$tablePrefix.'lead_pipelines.rotten_days'));

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.leads.index.datagrid.id'),
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'sales_person',
            'label' => trans('admin::app.leads.index.datagrid.sales-person'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
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
            'index' => 'title',
            'label' => trans('admin::app.leads.index.datagrid.subject'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'lead_source_name',
            'label' => trans('admin::app.leads.index.datagrid.source'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => $this->sourceRepository->all(['name as label', 'id as value'])->toArray(),
        ]);

        $this->addColumn([
            'index' => 'lead_value',
            'label' => trans('admin::app.leads.index.datagrid.lead-value'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => false,
            'filterable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->lead_value, 2),
        ]);

        $this->addColumn([
            'index' => 'lead_type_name',
            'label' => trans('admin::app.leads.index.datagrid.lead-type'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => $this->typeRepository->all(['name as label', 'id as value'])->toArray(),
        ]);

        $this->addColumn([
            'index' => 'tag_name',
            'label' => trans('admin::app.leads.index.datagrid.tag-name'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'closure' => fn ($row) => $row->tag_name ?? '--',
            'filterable_options' => [
                'repository' => TagRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
        ]);

        $this->addColumn([
            'index' => 'person_name',
            'label' => trans('admin::app.leads.index.datagrid.contact-person'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
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
                if (! $row->person_id) {
                    return '--';
                }

                $route = route('admin.contacts.persons.view', $row->person_id);

                return "<a class=\"text-brandColor transition-all hover:underline\" href='".$route."'>".$row->person_name.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'stage',
            'label' => trans('admin::app.leads.index.datagrid.stage'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => $this->pipeline->stages->pluck('name', 'id')
                ->map(function ($name, $id) {
                    return ['value' => $id, 'label' => $name];
                })
                ->values()
                ->all(),
        ]);

        $this->addColumn([
            'index' => 'rotten_lead',
            'label' => trans('admin::app.leads.index.datagrid.rotten-lead'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => false,
            'closure' => function ($row) {
                if (! $row->rotten_lead) {
                    return trans('admin::app.leads.index.datagrid.no');
                }

                if (in_array($row->stage_code, ['won', 'lost'])) {
                    return trans('admin::app.leads.index.datagrid.no');
                }

                return trans('admin::app.leads.index.datagrid.yes');
            },
        ]);

        $this->addColumn([
            'index' => 'expected_close_date',
            'label' => trans('admin::app.leads.index.datagrid.date-to'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'closure' => function ($row) {
                if (! $row->expected_close_date) {
                    return '--';
                }

                return $row->expected_close_date;
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.leads.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
        ]);

        $this->addColumn([
            'index'      => 'policy_number',
            'label'      => trans('admin::insurance.policy_attributes.policy_number'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'filterable' => true,
            'closure'    => fn ($row) => $row->policy_number ? '<span class="font-mono font-semibold">'.$row->policy_number.'</span>' : '--',
        ]);

        $this->addColumn([
            'index'      => 'policy_status',
            'label'      => trans('admin::insurance.policy_attributes.policy_status'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => false,
            'filterable' => true,
            'closure'    => function ($row) {
                if (empty($row->policy_status)) {
                    return '--';
                }
                $badgeClasses = match (strtolower(trim($row->policy_status))) {
                    'active / bound', 'activa / emitida' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                    'pending first payment', 'en espera de primer pago' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                    'under review', 'en revisión' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
                    'lapsed / cancelled', 'cancelada / lapsed' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
                    'renewed', 'renovada' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                };
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium '.$badgeClasses.'">'.$row->policy_status.'</span>';
            },
        ]);

        $this->addColumn([
            'index'           => 'renewal_date',
            'label'           => trans('admin::insurance.policy_attributes.renewal_date'),
            'type'            => 'date',
            'sortable'        => true,
            'searchable'      => false,
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'closure'         => fn ($row) => $row->renewal_date ? core()->formatDate($row->renewal_date, 'd/m/Y') : '--',
        ]);

        $this->addColumn([
            'index'      => 'issued_premium',
            'label'      => trans('admin::insurance.policy_attributes.issued_premium'),
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => false,
            'filterable' => true,
            'closure'    => fn ($row) => $row->issued_premium ? core()->formatBasePrice($row->issued_premium, 2) : '--',
        ]);

        /**
         * User defined attributes are hidden from the grid but included in the export so the data
         * entered into custom fields can be exported alongside the built-in columns.
         */
        if (request()->boolean('export')) {
            foreach ($this->getCustomAttributes() as $attribute) {
                $this->addColumn([
                    'index' => $attribute->code,
                    'label' => $attribute->name,
                    'type' => 'string',
                    'searchable' => false,
                    'sortable' => false,
                    'filterable' => false,
                    'visibility' => false,
                ]);
            }
        }
    }

    /**
     * Retrieve the user defined attributes for leads.
     */
    protected function getCustomAttributes(): Collection
    {
        static $attributes;

        return $attributes ??= Attribute::query()
            ->where('entity_type', 'leads')
            ->where('is_user_defined', 1)
            ->get();
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('leads.view')) {
            $this->addAction([
                'icon' => 'icon-eye',
                'title' => trans('admin::app.leads.index.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.leads.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('leads.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.leads.index.datagrid.delete'),
                'method' => 'delete',
                'url' => fn ($row) => route('admin.leads.delete', $row->id),
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
            'title' => trans('admin::app.leads.index.datagrid.mass-delete'),
            'method' => 'POST',
            'url' => route('admin.leads.mass_delete'),
        ]);

        $this->addMassAction([
            'title' => trans('admin::app.leads.index.datagrid.mass-update'),
            'url' => route('admin.leads.mass_update'),
            'method' => 'POST',
            'options' => $this->pipeline->stages->map(fn ($stage) => [
                'label' => $stage->name,
                'value' => $stage->id,
            ])->toArray(),
        ]);
    }
}
