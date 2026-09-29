<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Team automations: "when X happens, put a follow-up on someone's
         * plate (or tell them)". Triggers: lead created, stage entered,
         * renewal coming up, case overdue.
         */
        Schema::create('teamwork_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('name');
            $table->string('trigger', 30);
            $table->json('conditions')->nullable();
            $table->string('action', 20)->default('follow_up');
            $table->string('assign_to', 20)->default('owner');
            $table->unsignedInteger('assign_user_id')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->unsignedSmallInteger('due_in_days')->default(1);
            $table->text('note_template')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        /**
         * What each automation already did, so scheduled triggers never repeat.
         */
        Schema::create('teamwork_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('teamwork_automations')->cascadeOnDelete();
            $table->string('run_key', 120);
            $table->string('entity_type', 30);
            $table->unsignedInteger('entity_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['automation_id', 'run_key']);
        });

        // Suggested starters, paused until the agency turns them on.
        $now = now();

        DB::table('teamwork_automations')->insert([
            [
                'name' => 'Nuevo lead → primer contacto en 1 día hábil',
                'trigger' => 'lead_created',
                'conditions' => json_encode([]),
                'action' => 'follow_up',
                'assign_to' => 'owner',
                'priority' => 'normal',
                'due_in_days' => 1,
                'note_template' => 'Primer contacto con {client} ({lead}). Presentarse y agendar la llamada de calificación.',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'name' => 'Póliza emitida → llamada de bienvenida a los 7 días',
                'trigger' => 'stage_entered',
                'conditions' => json_encode(['stage_code' => 'won']),
                'action' => 'follow_up',
                'assign_to' => 'owner',
                'priority' => 'normal',
                'due_in_days' => 7,
                'note_template' => 'Llamada de bienvenida a {client}: confirmar tarjeta de ID, primer pago y resolver dudas.',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'name' => 'Renovación en 60 días → revisar plan con el cliente',
                'trigger' => 'renewal_upcoming',
                'conditions' => json_encode(['days_before' => 60]),
                'action' => 'follow_up',
                'assign_to' => 'owner',
                'priority' => 'normal',
                'due_in_days' => 5,
                'note_template' => 'La póliza de {client} renueva el {date}. Revisar cambios de ingresos, hogar y plan antes de la renovación.',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'name' => 'Caso atrasado → avisar al master agent',
                'trigger' => 'case_overdue',
                'conditions' => json_encode([]),
                'action' => 'notify',
                'assign_to' => 'master',
                'priority' => 'urgent',
                'due_in_days' => 0,
                'note_template' => '{lead} ({client}) lleva {idle} sin tocar en la etapa "{stage}".',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teamwork_automation_runs');
        Schema::dropIfExists('teamwork_automations');
    }
};
