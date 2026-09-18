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
        Schema::create('agency_hierarchies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->unique()->index()->comment('Agent / Producer');
            $table->integer('parent_user_id')->unsigned()->nullable()->index()->comment('Upline Manager / GA / MGA');
            $table->string('agency_tier', 40)->default('producer')->comment('fmo, mga, ga, producer, sub_agent');
            $table->string('sub_agency_name', 150)->nullable()->comment('Custom branch or sub-agency name');
            $table->string('npn_number', 30)->nullable()->comment('National Producer Number');
            $table->decimal('contract_level_percentage', 5, 2)->default(70.00)->comment('Base commission contract split %');
            $table->decimal('override_pmpm', 8, 2)->default(0.00)->comment('Fixed PMPM override paid to immediate upline per member/mo');
            $table->decimal('override_percentage', 5, 2)->default(0.00)->comment('Percentage override paid to upline');
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('policy_override_distributions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('policy_id')->unsigned()->nullable()->index();
            $table->integer('commission_id')->unsigned()->nullable()->index();
            $table->integer('writing_agent_id')->unsigned()->index()->comment('Producer who wrote policy');
            $table->integer('beneficiary_user_id')->unsigned()->index()->comment('Upline agent receiving the override');
            $table->tinyInteger('tier_level')->default(1)->comment('1: direct GA, 2: MGA, 3: FMO');
            $table->string('tier_name', 60)->nullable()->comment('e.g. General Agent (GA) Override');
            $table->decimal('rate_per_member', 8, 2)->default(0);
            $table->integer('members_count')->default(1);
            $table->decimal('override_amount', 12, 2)->default(0);
            $table->string('period_month', 10)->index()->comment('YYYY-MM billing period');
            $table->string('status', 30)->default('pending')->comment('pending, approved, paid, clawback');
            $table->timestamps();

            $table->foreign('writing_agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('beneficiary_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_override_distributions');
        Schema::dropIfExists('agency_hierarchies');
    }
};
