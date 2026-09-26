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
        Schema::create('lead_tax_households', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->index();
            $table->unsignedSmallInteger('tax_year')->default(2026);
            $table->string('state_code', 2)->default('FL');
            $table->unsignedTinyInteger('household_size')->default(1);
            $table->decimal('projected_annual_income', 12, 2)->default(0);

            // FPL & CSR calculations
            $table->decimal('fpl_guideline_threshold', 12, 2)->nullable();
            $table->decimal('fpl_percentage', 6, 2)->nullable();
            $table->string('fpl_category', 50)->nullable()->comment('medicaid_gap, silver_94, silver_87, silver_73, standard');
            $table->string('csr_tier', 50)->nullable()->comment('CSR 94%, CSR 87%, CSR 73%, Standard');
            $table->decimal('applicable_percentage', 5, 2)->nullable()->comment('ACA/IRA Maximum required income contribution %');

            // Financial Subsidies
            $table->decimal('max_annual_contribution', 12, 2)->nullable();
            $table->decimal('max_monthly_contribution', 12, 2)->nullable();
            $table->decimal('estimated_benchmark_premium', 12, 2)->nullable();
            $table->decimal('estimated_monthly_aptc', 12, 2)->nullable();
            $table->decimal('estimated_net_premium', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->unique(['lead_id', 'tax_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_tax_households');
    }
};
