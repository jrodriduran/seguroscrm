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
            if (! Schema::hasColumn('leads', 'sla_status')) {
                $table->string('sla_status', 20)->default('pending')->after('closed_at');
            }

            if (! Schema::hasColumn('leads', 'sla_hours')) {
                $table->unsignedSmallInteger('sla_hours')->default(2)->after('sla_status');
            }

            if (! Schema::hasColumn('leads', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('sla_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['sla_status', 'sla_hours', 'assigned_at']);
        });
    }
};
