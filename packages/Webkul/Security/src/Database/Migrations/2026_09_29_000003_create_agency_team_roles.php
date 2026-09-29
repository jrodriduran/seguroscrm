<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Starter roles for an independent agency: the owner keeps the existing
 * full-permission "Administrator" role (master agent); these cover the
 * people around them. Permissions stay editable in Settings > Roles.
 */
return new class extends Migration
{
    protected array $roles = [
        [
            'name' => 'Agente asistente',
            'description' => 'Trabaja la cartera del master agent: leads, contactos, cotizaciones, pólizas, actividades y correo. No borra, no ve comisiones ni configuración.',
            'permissions' => [
                'dashboard', 'follow_up',
                'leads', 'leads.create', 'leads.create.quick-create', 'leads.view', 'leads.edit',
                'quotes', 'quotes.create', 'quotes.mail', 'quotes.edit', 'quotes.print',
                'mail', 'mail.inbox', 'mail.draft', 'mail.outbox', 'mail.sent', 'mail.trash', 'mail.compose', 'mail.compose.quick-create', 'mail.view', 'mail.edit',
                'activities', 'activities.create', 'activities.edit',
                'contacts', 'contacts.persons', 'contacts.persons.create', 'contacts.persons.create.quick-create', 'contacts.persons.edit', 'contacts.persons.view',
                'contacts.organizations', 'contacts.organizations.create', 'contacts.organizations.create.quick-create', 'contacts.organizations.edit',
                'products', 'products.view',
                'policies', 'policies.edit',
            ],
        ],
        [
            'name' => 'Auditor (cumplimiento)',
            'description' => 'Solo lectura de toda la operación para auditorías y cumplimiento, incluido el registro de accesos. No crea, edita ni borra nada.',
            'permissions' => [
                'dashboard', 'follow_up',
                'leads', 'leads.view',
                'quotes', 'quotes.print',
                'mail', 'mail.inbox', 'mail.draft', 'mail.outbox', 'mail.sent', 'mail.trash', 'mail.view',
                'activities',
                'contacts', 'contacts.persons', 'contacts.persons.view', 'contacts.organizations',
                'products', 'products.view',
                'policies', 'commissions', 'hierarchy', 'insurance_analytics',
                'settings', 'settings.user', 'settings.user.groups', 'settings.user.roles', 'settings.user.users',
                'settings.security', 'settings.security.access_logs', 'settings.security.two_factor',
                'configuration',
            ],
        ],
        [
            'name' => 'Recepción (solo consulta)',
            'description' => 'Front desk: consulta clientes, leads y pólizas para atender llamadas, registra llamadas y citas y marca seguimientos para los agentes. No modifica registros.',
            'permissions' => [
                'dashboard', 'follow_up',
                'leads', 'leads.view',
                'contacts', 'contacts.persons', 'contacts.persons.view', 'contacts.organizations',
                'activities', 'activities.create',
                'policies',
            ],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->roles as $role) {
            if (DB::table('roles')->where('name', $role['name'])->exists()) {
                continue;
            }

            DB::table('roles')->insert([
                'name' => $role['name'],
                'description' => $role['description'],
                'permission_type' => 'custom',
                'permissions' => json_encode($role['permissions']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations: only removes the roles if nobody uses them.
     */
    public function down(): void
    {
        foreach ($this->roles as $role) {
            $id = DB::table('roles')->where('name', $role['name'])->value('id');

            if ($id && ! DB::table('users')->where('role_id', $id)->exists()) {
                DB::table('roles')->where('id', $id)->delete();
            }
        }
    }
};
