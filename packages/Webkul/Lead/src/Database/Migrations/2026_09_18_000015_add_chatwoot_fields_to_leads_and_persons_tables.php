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
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('chatwoot_conversation_id')->nullable()->after('lead_pipeline_stage_id');
            $table->unsignedBigInteger('chatwoot_inbox_id')->nullable()->after('chatwoot_conversation_id');
            $table->timestamp('chatwoot_last_message_at')->nullable()->after('chatwoot_inbox_id');

            $table->index('chatwoot_conversation_id');
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->unsignedBigInteger('chatwoot_contact_id')->nullable()->after('user_id');
            $table->string('chatwoot_contact_source_id', 100)->nullable()->after('chatwoot_contact_id');

            $table->index('chatwoot_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex(['chatwoot_contact_id']);
            $table->dropColumn(['chatwoot_contact_id', 'chatwoot_contact_source_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['chatwoot_conversation_id']);
            $table->dropColumn(['chatwoot_conversation_id', 'chatwoot_inbox_id', 'chatwoot_last_message_at']);
        });
    }
};
