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
        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->boolean('missing_commission_flag')->default(false)->index()->after('renewal_notes');
            $table->timestamp('missing_commission_detected_at')->nullable()->after('missing_commission_flag');
            $table->decimal('missing_commission_amount', 10, 2)->default(0)->after('missing_commission_detected_at');
            $table->integer('missing_commission_days')->default(0)->after('missing_commission_amount');
            $table->text('missing_commission_notes')->nullable()->after('missing_commission_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'missing_commission_flag',
                'missing_commission_detected_at',
                'missing_commission_amount',
                'missing_commission_days',
                'missing_commission_notes',
            ]);
        });
    }
};
