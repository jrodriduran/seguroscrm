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
        if (! Schema::hasTable('lead_sla_rules')) {
            Schema::create('lead_sla_rules', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('lead_pipeline_id')->nullable();
                $table->unsignedInteger('lead_type_id')->nullable();
                $table->unsignedSmallInteger('first_contact_hours')->default(2)->comment('SLA hours for first contact');
                $table->unsignedSmallInteger('follow_up_hours')->default(24)->comment('SLA hours between follow ups');
                $table->unsignedSmallInteger('escalation_hours')->default(4)->comment('Hours overdue before escalating to Master Agent');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('lead_pipeline_id')
                    ->references('id')
                    ->on('lead_pipelines')
                    ->onDelete('cascade');

                $table->foreign('lead_type_id')
                    ->references('id')
                    ->on('lead_types')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_sla_rules');
    }
};
