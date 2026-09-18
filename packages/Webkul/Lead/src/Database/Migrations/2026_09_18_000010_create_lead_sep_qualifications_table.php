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
        Schema::create('lead_sep_qualifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lead_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('event_type', 60)->comment('loss_of_coverage, marriage, birth_adoption, permanent_move, immigration_status, income_change, fema_exceptional');
            $table->date('event_date')->comment('Date when the Qualifying Life Event occurred or will occur');
            $table->date('sep_deadline')->comment('Calculated 60-day window deadline for enrollment');
            $table->date('effective_date')->nullable()->comment('Calculated coverage effective date');
            $table->boolean('is_eligible')->default(true);
            $table->json('required_documents')->nullable()->comment('CMS verification checklist items');
            $table->json('verified_documents')->nullable()->comment('Checklist items checked off by agent');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_sep_qualifications');
    }
};
