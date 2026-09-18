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
        Schema::table('insurance_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_policies', 'portal_token')) {
                $table->string('portal_token', 64)->nullable()->unique()->after('policy_number');
            }

            if (! Schema::hasColumn('insurance_policies', 'member_id')) {
                $table->string('member_id', 60)->nullable()->after('portal_token')->comment('Carrier Member ID');
            }

            if (! Schema::hasColumn('insurance_policies', 'group_number')) {
                $table->string('group_number', 60)->nullable()->after('member_id')->comment('Carrier Group Number');
            }

            if (! Schema::hasColumn('insurance_policies', 'pcp_name')) {
                $table->string('pcp_name', 120)->nullable()->after('plan_name')->comment('Primary Care Physician Name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropColumn(['portal_token', 'member_id', 'group_number', 'pcp_name']);
        });
    }
};
