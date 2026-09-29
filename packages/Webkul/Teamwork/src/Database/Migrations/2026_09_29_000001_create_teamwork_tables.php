<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * A record someone must follow up: flagged by its owner, assigned by a
         * teammate, or marked urgent by a supervisor. Works for any entity.
         */
        Schema::create('teamwork_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('entity_type', 30);
            $table->unsignedInteger('entity_id');
            $table->string('title');
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('assigned_to');
            $table->unsignedInteger('created_by');
            $table->string('priority', 10)->default('normal');
            $table->text('note')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 10)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['entity_type', 'entity_id']);

            $table->foreign('assigned_to')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });

        /**
         * Shared thread between whoever is involved in a follow-up.
         */
        Schema::create('teamwork_follow_up_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained('teamwork_follow_ups')->cascadeOnDelete();
            $table->unsignedInteger('user_id');
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        /**
         * When an open case counts as "warning" / "overdue", in business hours
         * without a touch. The most specific active rule wins: stage, then
         * pipeline, then the defaults in configuration.
         */
        Schema::create('teamwork_overdue_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->unsignedInteger('lead_pipeline_id')->nullable();
            $table->unsignedInteger('lead_pipeline_stage_id')->nullable();
            $table->unsignedSmallInteger('warning_hours');
            $table->unsignedSmallInteger('overdue_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('lead_pipeline_id')->references('id')->on('lead_pipelines')->cascadeOnDelete();
            $table->foreign('lead_pipeline_stage_id')->references('id')->on('lead_pipeline_stages')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teamwork_overdue_rules');
        Schema::dropIfExists('teamwork_follow_up_comments');
        Schema::dropIfExists('teamwork_follow_ups');
    }
};
