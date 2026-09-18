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
        if (! Schema::hasTable('lead_assignment_rules')) {
            Schema::create('lead_assignment_rules', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('lead_pipeline_id')->nullable()->unique();
                $table->string('strategy', 30)->default('round_robin')->comment('round_robin, least_loaded, manual');
                $table->unsignedSmallInteger('max_capacity')->default(0)->comment('Max active leads per agent; 0 = unlimited');
                $table->json('agent_ids')->nullable()->comment('JSON array of allowed user IDs');
                $table->unsignedInteger('rr_pointer')->default(0)->comment('Pointer to the current round robin agent index');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('lead_pipeline_id')
                    ->references('id')
                    ->on('lead_pipelines')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_assignment_rules');
    }
};
