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
        Schema::create('lead_consents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->string('token', 64)->unique();
            $table->string('status', 30)->default('pending'); // pending, signed, revoked

            // Consumer details
            $table->string('client_name', 150);
            $table->string('client_phone', 50)->nullable();
            $table->string('client_email', 100)->nullable();

            // Agent / Broker details
            $table->string('agent_name', 100)->nullable();
            $table->string('agent_npn', 50)->nullable();
            $table->string('agency_name', 150)->nullable();

            // Legal text & compliance
            $table->text('consent_text')->nullable();

            // Signature & Audit trail (CMS 45 CFR § 155.220)
            $table->longText('signature_data')->nullable(); // Base64 data URL
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();

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
        Schema::dropIfExists('lead_consents');
    }
};
