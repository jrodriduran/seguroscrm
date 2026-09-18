<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\AssignmentRuleProxy;
use Webkul\Lead\Models\PipelineProxy;
use Webkul\Lead\Models\SlaRuleProxy;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\User\Models\RoleProxy;
use Webkul\User\Models\UserProxy;

class TestEnvironmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = RoleProxy::modelClass()::first();
        $roleId = $adminRole ? $adminRole->id : 1;

        $password = Hash::make('Prueba01#');

        // 1. Create or update Master Agent
        $ma = UserProxy::modelClass()::updateOrCreate(
            ['email' => 'master@prueba.ai'],
            [
                'name'            => 'Master Agent',
                'password'        => $password,
                'role_id'         => $roleId,
                'status'          => 1,
                'view_permission' => 'global',
            ]
        );

        // 2. Create or update Agent 1
        $agent1 = UserProxy::modelClass()::updateOrCreate(
            ['email' => 'agent1@prueba.ai'],
            [
                'name'            => 'Agente 1 (Carlos Ruiz)',
                'password'        => $password,
                'role_id'         => $roleId,
                'status'          => 1,
                'view_permission' => 'global',
            ]
        );

        // 3. Create or update Agent 2
        $agent2 = UserProxy::modelClass()::updateOrCreate(
            ['email' => 'agent2@prueba.ai'],
            [
                'name'            => 'Agente 2 (Laura Gómez)',
                'password'        => $password,
                'role_id'         => $roleId,
                'status'          => 1,
                'view_permission' => 'global',
            ]
        );

        // 4. Configure Pipeline & Rules
        $pipeline = PipelineProxy::modelClass()::first();
        $pipelineId = $pipeline ? $pipeline->id : 1;

        // SLA Rules
        SlaRuleProxy::modelClass()::updateOrCreate(
            ['lead_pipeline_id' => $pipelineId],
            [
                'first_contact_hours' => 2,
                'follow_up_hours'     => 24,
                'escalation_hours'    => 4,
                'is_active'           => 1,
            ]
        );

        // Assignment Rules
        AssignmentRuleProxy::modelClass()::updateOrCreate(
            ['lead_pipeline_id' => $pipelineId],
            [
                'strategy'     => 'round_robin',
                'max_capacity' => 15,
                'agent_ids'    => [$agent1->id, $agent2->id],
                'rr_pointer'   => 0,
                'is_active'    => 1,
            ]
        );

        // 5. Persons / Clients
        $person1 = PersonProxy::modelClass()::updateOrCreate(
            ['name' => 'Juan Pérez'],
            ['emails' => [['value' => 'juan.perez@example.com', 'label' => 'work']]]
        );

        $person2 = PersonProxy::modelClass()::updateOrCreate(
            ['name' => 'Transporte Express C.A.'],
            ['emails' => [['value' => 'contacto@transporteexpress.com', 'label' => 'work']]]
        );

        $person3 = PersonProxy::modelClass()::updateOrCreate(
            ['name' => 'Roberto Díaz'],
            ['emails' => [['value' => 'roberto.diaz@example.com', 'label' => 'work']]]
        );

        $person4 = PersonProxy::modelClass()::updateOrCreate(
            ['name' => 'Sofía Morales'],
            ['emails' => [['value' => 'sofia.morales@example.com', 'label' => 'work']]]
        );

        $person5 = PersonProxy::modelClass()::updateOrCreate(
            ['name' => 'María Delgado'],
            ['emails' => [['value' => 'maria.delgado@example.com', 'label' => 'work']]]
        );

        $leadRepo = app(LeadRepository::class);
        $now = Carbon::now();

        // Clean previous sample leads if needed
        DB::table('leads')->whereIn('title', [
            'Póliza ACA Salud Familiar - Juan Pérez',
            'Seguro de Auto Comercial - Transporte Express',
            'Póliza de Vida Gastos Finales - Roberto Díaz',
            'Póliza Dental & Visión - Sofía Morales',
            'Seguro de Salud Individual - María Delgado',
        ])->delete();

        // 6. Test Leads via LeadRepository
        // Lead 1: Pending (Agent 1)
        $lead1 = $leadRepo->create([
            'entity_type'            => 'leads',
            'title'                  => 'Póliza ACA Salud Familiar - Juan Pérez',
            'lead_value'             => 480.00,
            'status'                 => 1,
            'user_id'                => $agent1->id,
            'person_id'              => $person1->id,
            'lead_pipeline_id'       => $pipelineId,
            'lead_pipeline_stage_id' => 1,
            'sla_status'             => 'pending',
            'sla_hours'              => 2,
            'assigned_at'            => $now->copy()->subMinutes(30),
        ]);

        $act1 = ActivityProxy::modelClass()::create([
            'title'               => 'Llamada Primer Contacto (SLA 2h)',
            'type'                => 'call',
            'comment'             => 'Presentar opciones de cobertura médica ACA.',
            'schedule_from'       => $now->copy()->subMinutes(30),
            'schedule_to'         => $now->copy()->addMinutes(90),
            'is_done'             => false,
            'user_id'             => $agent1->id,
            'priority'            => 'urgent',
            'sla_activity_status' => 'pending',
        ]);
        $act1->leads()->syncWithoutDetaching([$lead1->id]);

        // Lead 2: Overdue (Agent 2)
        $lead2 = $leadRepo->create([
            'entity_type'            => 'leads',
            'title'                  => 'Seguro de Auto Comercial - Transporte Express',
            'lead_value'             => 1250.00,
            'status'                 => 1,
            'user_id'                => $agent2->id,
            'person_id'              => $person2->id,
            'lead_pipeline_id'       => $pipelineId,
            'lead_pipeline_stage_id' => 1,
            'sla_status'             => 'overdue',
            'sla_hours'              => 2,
            'assigned_at'            => $now->copy()->subHours(5),
        ]);

        $act2 = ActivityProxy::modelClass()::create([
            'title'               => 'Llamada Urgente Cotización Flota',
            'type'                => 'call',
            'comment'             => 'SLA vencido: cliente esperando cotización de flota de 6 camiones.',
            'schedule_from'       => $now->copy()->subHours(5),
            'schedule_to'         => $now->copy()->subHours(3),
            'is_done'             => false,
            'user_id'             => $agent2->id,
            'priority'            => 'urgent',
            'sla_activity_status' => 'overdue',
        ]);
        $act2->leads()->syncWithoutDetaching([$lead2->id]);

        // Lead 3: Escalated to Master Agent (Agent 1)
        $lead3 = $leadRepo->create([
            'entity_type'            => 'leads',
            'title'                  => 'Póliza de Vida Gastos Finales - Roberto Díaz',
            'lead_value'             => 750.00,
            'status'                 => 1,
            'user_id'                => $agent1->id,
            'person_id'              => $person3->id,
            'lead_pipeline_id'       => $pipelineId,
            'lead_pipeline_stage_id' => 1,
            'sla_status'             => 'escalated',
            'sla_hours'              => 2,
            'assigned_at'            => $now->copy()->subHours(8),
            'escalated_at'           => $now->copy()->subHour(),
            'escalation_reason'      => 'Cliente no responde llamadas, requiere intervención del Agente Maestro',
        ]);

        $act3 = ActivityProxy::modelClass()::create([
            'title'               => '🚨 ESCALACIÓN SLA: Roberto Díaz',
            'type'                => 'note',
            'comment'             => 'Caso escalado a Torre de Control. Motivo: Cliente no responde llamadas.',
            'schedule_from'       => $now->copy()->subHour(),
            'schedule_to'         => $now->copy()->addHour(),
            'is_done'             => false,
            'user_id'             => $ma->id,
            'priority'            => 'urgent',
            'sla_activity_status' => 'overdue',
        ]);
        $act3->leads()->syncWithoutDetaching([$lead3->id]);

        // Lead 4: Unassigned (Waiting for distribution)
        $leadRepo->create([
            'entity_type'            => 'leads',
            'title'                  => 'Póliza Dental & Visión - Sofía Morales',
            'lead_value'             => 220.00,
            'status'                 => 1,
            'user_id'                => null,
            'person_id'              => $person4->id,
            'lead_pipeline_id'       => $pipelineId,
            'lead_pipeline_stage_id' => 1,
            'sla_status'             => 'pending',
            'sla_hours'              => 2,
            'assigned_at'            => null,
        ]);

        // Lead 5: Active (On time, Agent 2)
        $leadRepo->create([
            'entity_type'            => 'leads',
            'title'                  => 'Seguro de Salud Individual - María Delgado',
            'lead_value'             => 390.00,
            'status'                 => 1,
            'user_id'                => $agent2->id,
            'person_id'              => $person5->id,
            'lead_pipeline_id'       => $pipelineId,
            'lead_pipeline_stage_id' => 1,
            'sla_status'             => 'active',
            'sla_hours'              => 2,
            'assigned_at'            => $now->copy()->subHours(2),
        ]);
    }
}
