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
        Schema::table('quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('quotes', 'carrier_name')) {
                $table->string('carrier_name', 100)->nullable()->after('subject')->comment('Insurance carrier (e.g. Ambetter, Florida Blue, Oscar)');
            }

            if (! Schema::hasColumn('quotes', 'plan_name')) {
                $table->string('plan_name', 150)->nullable()->after('carrier_name')->comment('Health insurance plan name');
            }

            if (! Schema::hasColumn('quotes', 'metal_tier')) {
                $table->string('metal_tier', 30)->nullable()->after('plan_name')->comment('bronze, silver, gold, platinum, catastrophic');
            }

            if (! Schema::hasColumn('quotes', 'gross_premium')) {
                $table->decimal('gross_premium', 12, 2)->nullable()->after('metal_tier')->comment('Full monthly premium before subsidy');
            }

            if (! Schema::hasColumn('quotes', 'aptc_subsidy')) {
                $table->decimal('aptc_subsidy', 12, 2)->nullable()->after('gross_premium')->comment('Estimated federal APTC tax subsidy');
            }

            if (! Schema::hasColumn('quotes', 'net_premium')) {
                $table->decimal('net_premium', 12, 2)->nullable()->after('aptc_subsidy')->comment('Net monthly premium paid by client');
            }

            if (! Schema::hasColumn('quotes', 'deductible')) {
                $table->decimal('deductible', 12, 2)->nullable()->after('net_premium')->comment('Individual medical deductible');
            }

            if (! Schema::hasColumn('quotes', 'out_of_pocket_max')) {
                $table->decimal('out_of_pocket_max', 12, 2)->nullable()->after('deductible')->comment('Maximum out of pocket expenses');
            }

            if (! Schema::hasColumn('quotes', 'network_type')) {
                $table->string('network_type', 30)->nullable()->after('out_of_pocket_max')->comment('HMO, EPO, PPO, POS');
            }

            if (! Schema::hasColumn('quotes', 'copay_primary_care')) {
                $table->decimal('copay_primary_care', 12, 2)->nullable()->after('network_type')->comment('Copay for Primary Care Physician');
            }

            if (! Schema::hasColumn('quotes', 'copay_specialist')) {
                $table->decimal('copay_specialist', 12, 2)->nullable()->after('copay_primary_care')->comment('Copay for Medical Specialist');
            }

            if (! Schema::hasColumn('quotes', 'copay_generic_drugs')) {
                $table->decimal('copay_generic_drugs', 12, 2)->nullable()->after('copay_specialist')->comment('Copay for Generic Rx Drugs');
            }

            if (! Schema::hasColumn('quotes', 'quote_status')) {
                $table->string('quote_status', 30)->default('draft')->after('copay_generic_drugs')->comment('draft, presented, accepted, bound, rejected');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'carrier_name',
                'plan_name',
                'metal_tier',
                'gross_premium',
                'aptc_subsidy',
                'net_premium',
                'deductible',
                'out_of_pocket_max',
                'network_type',
                'copay_primary_care',
                'copay_specialist',
                'copay_generic_drugs',
                'quote_status',
            ]);
        });
    }
};
