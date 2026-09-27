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
        Schema::create('policy_service_cases', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ticket_number', 50)->unique();
            $table->unsignedInteger('policy_id')->index();
            $table->unsignedInteger('lead_id')->nullable()->index();
            $table->unsignedInteger('person_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index()->comment('Assigned agent');

            $table->string('category', 50)->default('general')
                ->comment('tax_1095a, address_change, income_update, pcp_change, id_card_replacement, claims_billing, dependent_change, general');
            $table->string('priority', 20)->default('normal')
                ->comment('low, normal, high, urgent');
            $table->string('status', 30)->default('open')
                ->comment('open, in_progress, pending_carrier, pending_client, resolved, closed');

            $table->string('subject', 255);
            $table->text('description')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->string('attachment_path', 255)->nullable()->comment('Uploaded 1095-A, proof or form');
            $table->boolean('is_shared_with_client')->default(false)->comment('Expose to insured portal');

            $table->timestamps();

            $table->foreign('policy_id')->references('id')->on('insurance_policies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('policy_service_case_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('service_case_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();

            $table->text('comment');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            $table->foreign('service_case_id')->references('id')->on('policy_service_cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_service_case_comments');
        Schema::dropIfExists('policy_service_cases');
    }
};
