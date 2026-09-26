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
        Schema::create('lead_consent_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_consent_id')->index();
            $table->unsignedInteger('lead_id')->index();
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->string('status', 30)->default('signed'); // signed, revoked, superseded

            $table->string('client_name', 150);
            $table->string('client_phone', 50)->nullable();
            $table->string('client_email', 100)->nullable();

            $table->string('agent_name', 100)->nullable();
            $table->string('agent_npn', 50)->nullable();
            $table->string('agency_name', 150)->nullable();

            $table->text('consent_text')->nullable();

            // Legal electronic signature & audit details (CMS 45 CFR § 155.220 - 10-year rule)
            $table->longText('signature_data')->nullable(); // Base64 data URL
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();

            // Cryptographic tampering verification hash
            $table->string('file_hash', 64)->nullable()->comment('SHA-256 audit fingerprint');

            // Formal revocation metadata
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();

            $table->timestamps();

            $table->foreign('lead_consent_id')->references('id')->on('lead_consents')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_consent_versions');
    }
};
