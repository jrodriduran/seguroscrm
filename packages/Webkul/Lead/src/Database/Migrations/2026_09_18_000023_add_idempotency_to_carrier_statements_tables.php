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
        Schema::table('carrier_statements', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('file_path')->index()->comment('SHA-256 cryptographic hash of CSV content');
            $table->integer('duplicate_records')->default(0)->after('missed_records')->comment('Count of blocked duplicate line payments');
            $table->decimal('total_duplicate_amount', 12, 4)->default(0.0000)->after('total_missed_amount')->comment('Amount of blocked duplicate payments');
        });

        Schema::table('carrier_statement_items', function (Blueprint $table) {
            $table->string('period_month', 7)->nullable()->after('policy_number')->index()->comment('Commission service period month (YYYY-MM)');
            $table->boolean('is_duplicate')->default(false)->after('match_status')->index()->comment('True if duplicate payment attempt blocked');
            $table->unsignedInteger('duplicate_of_item_id')->nullable()->after('is_duplicate')->comment('Reference to original statement item paid');

            $table->foreign('duplicate_of_item_id')->references('id')->on('carrier_statement_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carrier_statement_items', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_item_id']);
            $table->dropColumn([
                'period_month',
                'is_duplicate',
                'duplicate_of_item_id',
            ]);
        });

        Schema::table('carrier_statements', function (Blueprint $table) {
            $table->dropColumn([
                'file_hash',
                'duplicate_records',
                'total_duplicate_amount',
            ]);
        });
    }
};
