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
        Schema::create('agent_commission_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique()->index()->comment('Agent User ID');
            $table->decimal('total_earned', 12, 2)->default(0.00)->comment('Total commission & overrides earned all-time');
            $table->decimal('total_clawbacks', 12, 2)->default(0.00)->comment('Total clawbacks/chargebacks deducted all-time');
            $table->decimal('total_paid_out', 12, 2)->default(0.00)->comment('Total cash payouts disbursed to agent');
            $table->decimal('current_balance', 12, 2)->default(0.00)->comment('Net available balance: earned - clawbacks - paid_out');
            $table->decimal('held_reserve', 12, 2)->default(0.00)->comment('Rolling clawback reserve escrow balance');
            $table->string('status', 30)->default('in_good_standing')->index()->comment('in_good_standing, negative_balance_alert, suspended');
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('agent_commission_ledger_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('transaction_type', 40)->index()->comment('commission_credit, override_credit, clawback_debit, payout_disbursement, adjustment, reserve_withhold, reserve_release');
            $table->decimal('amount', 12, 2)->comment('Signed transaction amount (+ for credit, - for debit/payout)');
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('period_month', 7)->nullable()->index()->comment('YYYY-MM');
            $table->string('carrier_name', 100)->nullable();
            $table->unsignedInteger('policy_id')->nullable()->index();
            $table->string('policy_number', 80)->nullable();
            $table->unsignedInteger('statement_id')->nullable()->index();
            $table->unsignedInteger('statement_item_id')->nullable()->index();
            $table->string('reference_code', 80)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('policy_id')->references('id')->on('insurance_policies')->onDelete('set null');
            $table->foreign('statement_id')->references('id')->on('carrier_statements')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_commission_ledger_transactions');
        Schema::dropIfExists('agent_commission_balances');
    }
};
