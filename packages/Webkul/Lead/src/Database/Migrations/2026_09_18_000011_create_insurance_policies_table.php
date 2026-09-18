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
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('policy_number', 80)->index();
            $table->integer('lead_id')->unsigned()->nullable()->index();
            $table->integer('person_id')->unsigned()->nullable()->index();
            $table->integer('user_id')->unsigned()->nullable()->index()->comment('Writing Agent');
            $table->integer('quote_id')->unsigned()->nullable()->index();

            $table->string('carrier_name', 100)->index();
            $table->string('plan_name', 150)->nullable();
            $table->string('metal_tier', 30)->nullable()->comment('bronze, silver, gold, platinum, catastrophic');
            $table->string('network_type', 30)->nullable()->comment('HMO, EPO, PPO, POS');
            $table->string('market_type', 50)->default('aca_individual')->comment('aca_individual, medicare_advantage, medicare_supplement, life');

            $table->decimal('gross_premium', 12, 2)->default(0);
            $table->decimal('aptc_subsidy', 12, 2)->default(0);
            $table->decimal('net_premium', 12, 2)->default(0)->comment('Client monthly premium');

            $table->date('effective_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->date('paid_to_date')->nullable()->comment('Date through which monthly premium is paid');

            $table->string('status', 40)->default('active')->index()->comment('active, grace_period_1, grace_period_2_3, cancelled, renewed, pending');
            $table->date('grace_period_start_date')->nullable();
            $table->integer('grace_period_days')->default(0);

            $table->integer('members_count')->default(1)->comment('Number of covered lives');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
