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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('state_code', 2)->nullable()->index()->after('lead_pipeline_stage_id');
            $table->string('preferred_language', 10)->default('es')->index()->after('state_code');
            $table->string('assignment_failure_reason', 100)->nullable()->after('preferred_language');
            $table->boolean('has_tcpa_consent')->default(false)->index()->after('assignment_failure_reason');
            $table->timestamp('tcpa_consented_at')->nullable()->after('has_tcpa_consent');
            $table->string('tcpa_consent_type', 50)->nullable()->after('tcpa_consented_at'); // web_form_optin, inbound_call_verbal, signed_consent_doc, sms_optin_keyword
            $table->string('tcpa_consent_proof', 255)->nullable()->after('tcpa_consent_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('spoken_languages')->nullable()->after('ahip_certified_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'state_code',
                'preferred_language',
                'assignment_failure_reason',
                'has_tcpa_consent',
                'tcpa_consented_at',
                'tcpa_consent_type',
                'tcpa_consent_proof',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('spoken_languages');
        });
    }
};
