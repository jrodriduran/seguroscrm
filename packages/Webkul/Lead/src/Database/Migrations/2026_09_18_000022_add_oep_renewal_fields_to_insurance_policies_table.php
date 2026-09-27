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
            $table->unsignedSmallInteger('plan_year')->nullable()->after('market_type')->index()->comment('ACA / Medicare Plan Year (e.g. 2025, 2026)');
            $table->decimal('deductible', 12, 2)->default(0)->after('net_premium')->comment('Annual Medical Deductible');
            $table->decimal('max_out_of_pocket', 12, 2)->default(0)->after('deductible')->comment('Annual Max Out-Of-Pocket (MOOP)');
            $table->string('renewal_type', 50)->nullable()->after('prior_policy_id')->comment('same_plan, same_carrier_switch, cross_carrier_switch, auto_renewed');
            $table->unsignedSmallInteger('renewal_cohort_year')->nullable()->after('renewal_type')->index()->comment('Target OEP campaign year');
            $table->text('renewal_notes')->nullable()->after('renewal_cohort_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'plan_year',
                'deductible',
                'max_out_of_pocket',
                'renewal_type',
                'renewal_cohort_year',
                'renewal_notes',
            ]);
        });
    }
};
