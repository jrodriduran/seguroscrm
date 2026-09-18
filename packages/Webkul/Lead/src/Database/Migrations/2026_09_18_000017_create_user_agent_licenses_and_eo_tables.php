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
        // Add E&O and AHIP certification fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('npn', 30)->nullable()->after('email');
            $table->string('eo_carrier', 100)->nullable()->after('npn');
            $table->string('eo_policy_number', 80)->nullable()->after('eo_carrier');
            $table->date('eo_expires_at')->nullable()->after('eo_policy_number');
            $table->unsignedSmallInteger('ahip_certified_year')->nullable()->after('eo_expires_at');
        });

        // State insurance licenses for agents (Resident & Non-Resident)
        Schema::create('user_agent_licenses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');

            $table->string('state_code', 2); // FL, TX, GA, NC, etc.
            $table->string('license_number', 60);
            $table->string('license_type', 30)->default('resident'); // resident, non_resident
            $table->json('lines_of_authority')->nullable(); // health, life, variable, etc.
            $table->date('expires_at');
            $table->string('status', 30)->default('active'); // active, expiring_soon, expired

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'state_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_agent_licenses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['npn', 'eo_carrier', 'eo_policy_number', 'eo_expires_at', 'ahip_certified_year']);
        });
    }
};
