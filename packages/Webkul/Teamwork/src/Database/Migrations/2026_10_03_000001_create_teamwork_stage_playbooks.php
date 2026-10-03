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
         * How the team works each pipeline stage: whether leads may move on
         * with milestones missing, and what happens when a lead arrives.
         */
        Schema::create('teamwork_stage_playbooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_pipeline_stage_id')->unique();
            $table->string('gate', 10)->default('warn');
            $table->boolean('entry_task')->default(false);
            $table->string('entry_task_title')->nullable();
            $table->unsignedSmallInteger('entry_task_hours')->default(24);
            $table->string('entry_assign', 10)->default('owner');
            $table->unsignedInteger('entry_user_id')->nullable();
            $table->boolean('notify_owner')->default(true);
            $table->boolean('notify_master')->default(false);
            $table->boolean('carry_over')->default(true);
            $table->boolean('close_previous')->default(true);
            $table->timestamps();

            $table->foreign('lead_pipeline_stage_id')->references('id')->on('lead_pipeline_stages')->cascadeOnDelete();
        });

        /**
         * What must be true before a lead leaves a stage. Automatic ones are
         * checked against the record; manual ones are ticked by the agent.
         */
        Schema::create('teamwork_stage_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_pipeline_stage_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');
            $table->string('check', 30)->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('help', 300)->nullable();
            $table->timestamps();

            $table->index(['lead_pipeline_stage_id', 'position'], 'tw_stage_milestones_stage_index');
            $table->foreign('lead_pipeline_stage_id')->references('id')->on('lead_pipeline_stages')->cascadeOnDelete();
        });

        Schema::create('teamwork_lead_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->foreignId('milestone_id')->constrained('teamwork_stage_milestones')->cascadeOnDelete();
            $table->unsignedInteger('done_by')->nullable();
            $table->timestamp('done_at');
            $table->string('note', 300)->nullable();

            $table->unique(['lead_id', 'milestone_id'], 'tw_lead_milestones_unique');
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });

        /**
         * Moves made with required milestones missing (owner's override).
         */
        Schema::create('teamwork_stage_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('from_stage_id')->nullable();
            $table->unsignedInteger('to_stage_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('reason', 500);
            $table->json('missing')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });

        // Follow-ups created by a playbook, so leaving the stage can close them.
        Schema::table('teamwork_follow_ups', function (Blueprint $table) {
            $table->string('source', 60)->nullable()->after('url')->index();
        });

        $this->seedDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teamwork_follow_ups', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });

        Schema::dropIfExists('teamwork_stage_overrides');
        Schema::dropIfExists('teamwork_lead_milestones');
        Schema::dropIfExists('teamwork_stage_milestones');
        Schema::dropIfExists('teamwork_stage_playbooks');
    }

    /**
     * Sensible milestones per stage code (insurance pipelines), in "warn"
     * mode so nothing is blocked until the agency decides.
     *
     * [name, check|null, required]
     */
    protected function seedDefaults(): void
    {
        $byCode = [
            'new' => [
                ['Datos de contacto (teléfono o email)', 'contact_info', true],
                ['Primer contacto registrado (llamada)', 'call_logged', false],
            ],
            'follow-up' => [['Contacto confirmado con el cliente', 'call_logged', true]],
            'contact' => [['Contacto confirmado con el cliente', 'call_logged', true], ['Necesidades identificadas', null, false]],
            'census' => [
                ['Miembros del hogar registrados', 'household', true],
                ['Ingreso anual estimado', 'income', true],
                ['Fecha de nacimiento del titular', 'dob', false],
            ],
            'prospect' => [['Necesidades identificadas', null, true]],
            'quoted' => [
                ['Cotización creada', 'quote', true],
                ['El cliente eligió un plan', null, true],
            ],
            'consent_docs' => [
                ['Consentimiento CMS firmado', 'cms_consent', true],
                ['Consentimiento para contactar (TCPA)', 'contact_consent', false],
                ['Documentos del cliente cargados', 'documents', false],
            ],
            'soa' => [['Scope of Appointment (SOA) firmado', 'soa_signed', true]],
            'needs' => [['Médicos y medicamentos registrados', 'doctors_rx', false], ['Necesidades del cliente evaluadas', null, true]],
            'presentation' => [['Plan presentado al cliente', null, true]],
            'underwriting' => [['Preguntas de salud completadas', null, true]],
            'proposal' => [['Propuesta presentada y aceptada', null, true]],
            'negotiation' => [['Propuesta enviada', null, true]],
            'closing' => [['Aplicación firmada', null, true]],
        ];

        $now = now();

        foreach (DB::table('lead_pipeline_stages')->get(['id', 'code']) as $stage) {
            DB::table('teamwork_stage_playbooks')->insert([
                'lead_pipeline_stage_id' => $stage->id,
                'gate' => 'warn',
                'notify_master' => $stage->code === 'won',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($byCode[$stage->code] ?? [] as $position => [$name, $check, $required]) {
                DB::table('teamwork_stage_milestones')->insert([
                    'lead_pipeline_stage_id' => $stage->id,
                    'position' => $position + 1,
                    'name' => $name,
                    'check' => $check,
                    'is_required' => $required,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
