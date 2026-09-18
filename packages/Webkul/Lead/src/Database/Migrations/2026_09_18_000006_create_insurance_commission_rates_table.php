<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_commission_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('carrier_name', 100);
            $table->string('metal_tier', 50)->default('All'); // Bronze, Silver, Gold, Platinum, All
            $table->string('commission_type', 30)->default('pmpm'); // pmpm (per member per month), flat
            $table->decimal('rate_per_member', 12, 4)->default(25.0000);
            $table->integer('effective_year')->default(2026);
            $table->timestamps();
        });

        // Seed default ACA carrier rates for Florida/National ACA market
        DB::table('insurance_commission_rates')->insert([
            [
                'carrier_name' => 'Florida Blue',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 28.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'carrier_name' => 'Ambetter (Sunshine State)',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 26.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'carrier_name' => 'Oscar Health',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 25.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'carrier_name' => 'Molina Healthcare',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 24.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'carrier_name' => 'UnitedHealthcare (UHC)',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 27.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'carrier_name' => 'Aetna CVS Health',
                'metal_tier' => 'All',
                'commission_type' => 'pmpm',
                'rate_per_member' => 25.0000,
                'effective_year' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_commission_rates');
    }
};
