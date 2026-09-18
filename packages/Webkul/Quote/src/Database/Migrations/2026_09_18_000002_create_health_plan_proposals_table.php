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
        Schema::create('health_plan_proposals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lead_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('token', 64)->unique();
            $table->string('title', 200)->default('Comparativa de Opciones de Seguro de Salud');
            $table->json('quote_ids')->comment('Array of quote IDs included in the proposal comparison');
            $table->integer('selected_quote_id')->unsigned()->nullable()->comment('Plan chosen by client');
            $table->string('status', 30)->default('sent')->comment('sent, viewed, accepted, expired');
            $table->text('client_notes')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_plan_proposals');
    }
};
