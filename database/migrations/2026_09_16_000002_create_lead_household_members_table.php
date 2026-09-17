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
        if (! Schema::hasTable('lead_household_members')) {
            Schema::create('lead_household_members', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('lead_id')->unsigned();
                $table->string('name');
                $table->string('relationship')->default('spouse'); // spouse, child, parent, dependent, other
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->nullable(); // male, female, other
                $table->string('ssn_itin')->nullable();
                $table->string('immigration_status')->nullable();
                $table->boolean('is_applying_coverage')->default(1);
                $table->boolean('tobacco_user')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_household_members');
    }
};
