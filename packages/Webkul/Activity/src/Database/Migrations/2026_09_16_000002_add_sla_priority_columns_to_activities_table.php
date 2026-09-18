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
        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('is_done');
            }

            if (! Schema::hasColumn('activities', 'sla_activity_status')) {
                $table->string('sla_activity_status', 20)->nullable()->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['priority', 'sla_activity_status']);
        });
    }
};
