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
        // 1. Prescription Medications (Rx Collect & Formulary Management)
        Schema::create('lead_rx_medications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');

            $table->string('medication_name', 150);
            $table->string('dosage', 80)->nullable(); // e.g. 20mg, 500mg, 10ml
            $table->string('frequency', 80)->nullable(); // e.g. Once daily, Twice daily with food
            $table->integer('quantity_per_30_days')->default(30);

            // Formulary Tier classification
            // Tier 1: Preferred Generic, Tier 2: Generic, Tier 3: Preferred Brand, Tier 4: Non-Preferred, Tier 5: Specialty
            $table->string('drug_tier', 40)->default('Tier 1: Preferred Generic');

            // Utilization Management Restrictions
            $table->boolean('requires_prior_authorization')->default(false); // PA
            $table->boolean('requires_step_therapy')->default(false); // ST
            $table->boolean('has_quantity_limit')->default(false); // QL

            // Cost Estimates
            $table->decimal('estimated_copay_30d', 10, 2)->default(0.00); // 30-day retail
            $table->decimal('estimated_copay_90d_mail', 10, 2)->default(0.00); // 90-day mail order

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });

        // 2. Doctor & Medical Provider Network (Provider Lookup)
        Schema::create('lead_doctor_networks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');

            $table->string('doctor_name', 150);
            $table->string('specialty', 100)->default('Primary Care Physician (PCP)');
            $table->string('npi_number', 15)->nullable(); // National Provider Identifier (10 digits)
            $table->string('clinic_or_hospital', 180)->nullable();
            $table->string('address_city_state', 180)->nullable();
            $table->string('phone', 50)->nullable();

            // JSON map of carrier network statuses: {"Florida Blue": "In-Network", "Ambetter": "In-Network", ...}
            $table->json('carrier_network_status')->nullable();

            $table->boolean('is_primary_physician')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_doctor_networks');
        Schema::dropIfExists('lead_rx_medications');
    }
};
