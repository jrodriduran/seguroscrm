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
        /**
         * What the SaaS operator set for this instance: subscription state
         * (active, warning, read_only, suspended) and the message to show.
         */
        Schema::create('platform_state', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        /**
         * One-time support login links (valid for a minute).
         */
        Schema::create('platform_support_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->unsignedInteger('user_id');
            $table->string('reason', 200)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_support_tokens');
        Schema::dropIfExists('platform_state');
    }
};
