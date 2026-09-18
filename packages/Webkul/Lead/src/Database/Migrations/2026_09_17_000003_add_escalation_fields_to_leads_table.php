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
            if (! Schema::hasColumn('leads', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('assigned_at');
            }

            if (! Schema::hasColumn('leads', 'escalation_reason')) {
                $table->string('escalation_reason')->nullable()->after('escalated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['escalated_at', 'escalation_reason']);
        });
    }
};
