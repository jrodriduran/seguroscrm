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
        Schema::create('carrier_statements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('carrier_name', 100);
            $table->string('file_name', 255);
            $table->string('file_path', 255)->nullable();
            $table->string('period_month', 7); // e.g. 2026-02
            $table->integer('total_records')->default(0);
            $table->integer('matched_records')->default(0);
            $table->integer('discrepancy_records')->default(0);
            $table->integer('missed_records')->default(0);
            $table->decimal('total_carrier_amount', 12, 4)->default(0.0000);
            $table->decimal('total_expected_amount', 12, 4)->default(0.0000);
            $table->decimal('total_missed_amount', 12, 4)->default(0.0000);
            $table->string('status', 30)->default('completed'); // processing, completed, reviewed
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('carrier_statement_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('carrier_statement_id');
            $table->string('policy_number', 100)->index();
            $table->string('insured_name', 150)->nullable();
            $table->decimal('carrier_amount', 12, 4)->default(0.0000);
            $table->decimal('expected_amount', 12, 4)->default(0.0000);
            $table->decimal('difference', 12, 4)->default(0.0000);
            $table->string('match_status', 40)->default('matched_exact');
            // Values: matched_exact, matched_variance, missed_commission, unmatched_orphan, chargeback
            $table->unsignedInteger('commission_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('carrier_statement_id')->references('id')->on('carrier_statements')->onDelete('cascade');
            $table->foreign('commission_id')->references('id')->on('insurance_commissions')->onDelete('set null');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrier_statement_items');
        Schema::dropIfExists('carrier_statements');
    }
};
