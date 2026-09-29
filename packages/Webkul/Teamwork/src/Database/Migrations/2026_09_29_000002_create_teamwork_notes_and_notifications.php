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
         * A note sent to a teammate about a record. The recipient's read is
         * recorded (read receipt) and the note can be turned into a follow-up.
         */
        Schema::create('teamwork_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('entity_type', 30);
            $table->unsignedInteger('entity_id');
            $table->string('title');
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('from_user_id');
            $table->unsignedInteger('to_user_id');
            $table->text('body');
            $table->foreignId('reply_to_id')->nullable()->constrained('teamwork_notes')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('follow_up_id')->nullable()->constrained('teamwork_follow_ups')->nullOnDelete();
            $table->timestamps();

            $table->index(['to_user_id', 'read_at']);
            $table->index(['entity_type', 'entity_id']);

            $table->foreign('from_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        /**
         * In-app notifications (the bell): one row per recipient.
         */
        Schema::create('teamwork_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('type', 30);
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teamwork_notifications');
        Schema::dropIfExists('teamwork_notes');
    }
};
