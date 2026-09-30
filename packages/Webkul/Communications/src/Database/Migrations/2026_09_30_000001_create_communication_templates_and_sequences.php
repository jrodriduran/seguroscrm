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
         * Reusable messages. One template holds a version per channel
         * (email, WhatsApp, SMS) and language (es, en).
         */
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('code', 60)->nullable();
            $table->string('name');
            $table->string('category', 30)->default('general');
            $table->string('purpose', 15)->default('transactional');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('communication_template_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('communication_templates')->cascadeOnDelete();
            $table->string('channel', 15);
            $table->string('locale', 5);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->unique(['template_id', 'channel', 'locale'], 'cm_template_contents_unique');
        });

        /**
         * A sequence is a series of steps over time: messages, call tasks,
         * notices, physical deliveries… Triggers decide when a client enters.
         */
        Schema::create('communication_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('code', 60)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('pause_on_reply')->default(true);
            $table->boolean('exit_on_stage_change')->default(true);
            $table->boolean('allow_reentry')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('communication_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('communication_sequences')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('type', 20);
            $table->unsignedSmallInteger('delay_days')->default(0);
            $table->unsignedTinyInteger('send_hour')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->string('condition', 20)->default('always');
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['sequence_id', 'position']);
        });

        Schema::create('communication_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('communication_sequences')->cascadeOnDelete();
            $table->string('event', 30)->index();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * One client going through one sequence.
         */
        Schema::create('communication_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('communication_sequences')->cascadeOnDelete();
            $table->unsignedBigInteger('trigger_id')->nullable();
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('policy_id')->nullable();
            $table->string('unique_key', 190)->unique();
            $table->unsignedSmallInteger('next_position')->default(1);
            $table->timestamp('next_run_at')->nullable()->index();
            $table->string('status', 12)->default('active')->index();
            $table->string('exit_reason', 40)->nullable();
            $table->unsignedInteger('enrolled_by')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'status']);
            $table->index(['lead_id', 'status']);

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });

        Schema::create('communication_step_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('communication_enrollments')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('communication_sequence_steps')->nullOnDelete();
            $table->string('status', 15);
            $table->string('channel', 15)->nullable();
            $table->string('detail', 500)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        /**
         * When each lead entered and left each stage ("3 days in Quoted…").
         */
        Schema::create('communication_stage_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('stage_id');
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();

            $table->index(['stage_id', 'exited_at']);
            $table->index(['lead_id', 'exited_at']);

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });

        // Open leads start in their current stage.
        DB::table('leads')
            ->whereNotNull('lead_pipeline_stage_id')
            ->orderBy('id')
            ->get(['id', 'lead_pipeline_stage_id', 'updated_at', 'created_at'])
            ->each(fn ($lead) => DB::table('communication_stage_entries')->insert([
                'lead_id' => $lead->id,
                'stage_id' => $lead->lead_pipeline_stage_id,
                'entered_at' => $lead->updated_at ?? $lead->created_at ?? now(),
            ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_stage_entries');
        Schema::dropIfExists('communication_step_runs');
        Schema::dropIfExists('communication_enrollments');
        Schema::dropIfExists('communication_triggers');
        Schema::dropIfExists('communication_sequence_steps');
        Schema::dropIfExists('communication_sequences');
        Schema::dropIfExists('communication_template_contents');
        Schema::dropIfExists('communication_templates');
    }
};
