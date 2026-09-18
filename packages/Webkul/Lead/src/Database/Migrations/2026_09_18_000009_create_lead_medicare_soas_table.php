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
        Schema::create('lead_medicare_soas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // Assigned agent

            $table->string('token', 64)->unique();
            $table->string('status', 30)->default('pending'); // pending, signed, appointment_eligible, completed, revoked

            // Beneficiary details
            $table->string('beneficiary_name', 150);
            $table->string('beneficiary_phone', 50)->nullable();
            $table->string('beneficiary_address', 255)->nullable();
            $table->string('medicare_number', 50)->nullable();

            // Certified Agent details
            $table->string('agent_name', 100);
            $table->string('agent_npn', 50);
            $table->string('agent_phone', 50)->nullable();
            $table->string('agency_name', 150)->nullable();

            // Products to discuss (CMS Scope checkboxes)
            $table->boolean('discuss_medicare_advantage')->default(true);
            $table->boolean('discuss_prescription_drug')->default(true);
            $table->boolean('discuss_medigap')->default(false);
            $table->boolean('discuss_dental_vision')->default(false);
            $table->boolean('discuss_hospital_indemnity')->default(false);

            // CMS 48-Hour Rule Timestamps
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('appointment_eligible_at')->nullable(); // signed_at + 48 hours
            $table->timestamp('appointment_scheduled_at')->nullable();
            $table->string('exception_reason', 50)->default('none'); // none, walk_in, end_of_enrollment

            // Electronic Signature & Audit Trail
            $table->longText('signature_data')->nullable(); // Base64 data URL
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_medicare_soas');
    }
};
