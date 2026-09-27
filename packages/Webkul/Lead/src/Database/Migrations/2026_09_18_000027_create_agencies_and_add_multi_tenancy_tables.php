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
        Schema::create('agencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('ein_tax_id', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, inactive
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('agency_id')->nullable()->index()->after('role_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedInteger('agency_id')->nullable()->index()->after('user_id');
        });

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->unsignedInteger('agency_id')->nullable()->index()->after('user_id');
        });

        Schema::table('carrier_statements', function (Blueprint $table) {
            $table->unsignedInteger('agency_id')->nullable()->index()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carrier_statements', function (Blueprint $table) {
            $table->dropColumn('agency_id');
        });

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropColumn('agency_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('agency_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('agency_id');
        });

        Schema::dropIfExists('agencies');
    }
};
