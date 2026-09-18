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
        Schema::create('insurance_commissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('quote_id')->nullable();
            $table->unsignedInteger('user_id'); // Writing agent

            $table->string('policy_number', 100)->nullable();
            $table->string('carrier_name', 100);
            $table->string('plan_name', 150)->nullable();
            $table->string('metal_tier', 50)->nullable();
            $table->integer('members_count')->default(1);
            $table->string('commission_type', 30)->default('pmpm'); // pmpm, flat

            // Calculation
            $table->decimal('rate_per_member', 12, 4)->default(25.0000);
            $table->decimal('gross_monthly', 12, 4)->default(25.0000); // rate * members
            $table->decimal('agent_split_percentage', 5, 2)->default(70.00); // 70% agent, 30% agency
            $table->decimal('agent_monthly', 12, 4)->default(17.5000); // gross * split%
            $table->decimal('agency_monthly', 12, 4)->default(7.5000); // gross - agent_monthly

            $table->string('status', 30)->default('active'); // active, pending, paid, cancelled
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_commissions');
    }
};
