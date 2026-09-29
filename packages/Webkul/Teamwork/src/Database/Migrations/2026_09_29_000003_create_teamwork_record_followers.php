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
         * Who follows which record (like "Follow" in Salesforce Chatter):
         * followers are notified of what the team does on it.
         */
        Schema::create('teamwork_record_followers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('entity_type', 30);
            $table->unsignedInteger('entity_id');
            $table->boolean('is_auto')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teamwork_record_followers');
    }
};
