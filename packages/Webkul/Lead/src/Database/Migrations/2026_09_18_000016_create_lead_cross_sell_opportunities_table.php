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
        Schema::create('lead_cross_sell_opportunities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');

            // Product Type: dental_vision, hospital_indemnity, final_expense, critical_illness, cancer_protection
            $table->string('product_type', 60);
            $table->string('title', 150);
            $table->string('carrier_name', 100)->nullable();
            $table->decimal('estimated_monthly_premium', 10, 2)->default(0.00);
            $table->decimal('estimated_agent_commission', 10, 2)->default(0.00);

            // Status: recommended, presented, enrolled, declined
            $table->string('status', 30)->default('recommended');

            // Justification / Coverage Gap detected by the engine
            $table->text('gap_reason')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_cross_sell_opportunities');
    }
};
