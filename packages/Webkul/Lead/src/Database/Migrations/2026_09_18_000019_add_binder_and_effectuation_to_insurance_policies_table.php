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
            $table->string('binder_payment_status', 30)->default('pending')->after('status')->comment('pending, paid, waived_zero_premium, failed');
            $table->decimal('binder_amount', 12, 2)->default(0)->after('binder_payment_status');
            $table->date('binder_due_date')->nullable()->after('binder_amount');
            $table->timestamp('binder_paid_at')->nullable()->after('binder_due_date');
            $table->string('binder_confirmation_number', 100)->nullable()->after('binder_paid_at');
            $table->string('binder_payment_method', 50)->nullable()->after('binder_confirmation_number')->comment('credit_card, ach, carrier_portal, phone, waived_zero_premium');

            $table->date('effectuation_date')->nullable()->after('binder_payment_method');
            $table->string('effectuation_source', 60)->nullable()->after('effectuation_date')->comment('carrier_portal, ede_webhook, statement, agent_verified, zero_dollar_subsidy');
            $table->unsignedInteger('effectuation_verified_by')->nullable()->after('effectuation_source');

            $table->unsignedInteger('prior_policy_id')->nullable()->after('effectuation_verified_by')->comment('Year-over-year renewal parent link');

            $table->foreign('effectuation_verified_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('prior_policy_id')->references('id')->on('insurance_policies')->onDelete('set null');
        });

        Schema::create('policy_coverage_status_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('policy_id')->index();
            $table->unsignedInteger('lead_id')->nullable()->index();

            $table->string('from_status', 50);
            $table->string('to_status', 50);
            $table->string('binder_status', 50)->nullable();
            $table->string('source', 60)->default('agent_manual')->comment('carrier_portal, ede_webhook, statement, agent_manual, zero_dollar_subsidy');
            $table->text('reason')->nullable();

            $table->unsignedInteger('verified_by_user_id')->nullable();
            $table->timestamp('verified_at')->useCurrent();

            $table->timestamps();

            $table->foreign('policy_id')->references('id')->on('insurance_policies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('verified_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_coverage_status_histories');

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropForeign(['effectuation_verified_by']);
            $table->dropForeign(['prior_policy_id']);

            $table->dropColumn([
                'binder_payment_status',
                'binder_amount',
                'binder_due_date',
                'binder_paid_at',
                'binder_confirmation_number',
                'binder_payment_method',
                'effectuation_date',
                'effectuation_source',
                'effectuation_verified_by',
                'prior_policy_id',
            ]);
        });
    }
};
