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
         * Agency settings for communication channels (Chatwoot connection…).
         * Secret values are stored encrypted.
         */
        Schema::create('communication_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        /**
         * Every message exchanged with clients through a channel provider:
         * WhatsApp, SMS, Telegram, web chat… (Chatwoot today, email later).
         */
        Schema::create('communication_messages', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('external_id', 64)->nullable();
            $table->string('conversation_id', 64)->nullable()->index();
            $table->unsignedInteger('inbox_id')->nullable();
            $table->string('channel', 20);
            $table->string('direction', 3);
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('content')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 15)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index(['person_id', 'sent_at']);

            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_settings');
    }
};
