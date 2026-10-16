<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_household_transitions', function (Blueprint $table) {
            $table->id();
            // Restrict deletion: a member's transition history must survive routine edits.
            $table->unsignedInteger('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->restrictOnDelete();
            $table->unsignedInteger('household_member_id');
            $table->foreign('household_member_id')->references('id')->on('lead_household_members')->restrictOnDelete();
            $table->string('reason', 30);
            $table->string('plan_type', 20);
            $table->date('coverage_end_date');
            $table->date('review_on');
            $table->string('status', 20)->default('planned');
            $table->string('confirmation_reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('individual_lead_id')->nullable();
            $table->foreign('individual_lead_id')->references('id')->on('leads')->restrictOnDelete();
            $table->timestamps();
            $table->index(['lead_id', 'status'], 'hh_transition_lead_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_household_transitions');
    }
};
