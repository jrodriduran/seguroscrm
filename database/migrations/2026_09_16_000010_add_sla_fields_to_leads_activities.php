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
                $table->string('sla_status', 30)->default('pending')->after('status')->comment('SLA contact tracking status');
            }

            if (! Schema::hasColumn('leads', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('sla_status')->comment('Timestamp when the lead was last assigned to an agent');
            }

            if (! Schema::hasColumn('leads', 'sla_hours')) {
                $table->tinyInteger('sla_hours')->unsigned()->default(2)->after('assigned_at')->comment('SLA window in hours before lead is marked overdue');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('is_done')->comment('Task priority set by Master Agent');
            }

            if (! Schema::hasColumn('activities', 'sla_activity_status')) {
                $table->string('sla_activity_status', 30)->default('pending')->after('priority')->comment('SLA status for this specific activity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(array_filter(['sla_status', 'assigned_at', 'sla_hours'], fn ($c) => Schema::hasColumn('leads', $c)));
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(array_filter(['priority', 'sla_activity_status'], fn ($c) => Schema::hasColumn('activities', $c)));
        });
    }
};
