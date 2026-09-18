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
        Schema::create('lead_dmi_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('person_id')->nullable();

            // Type of DMI requirement
            // income, immigration, citizenship, ssn, incarceration, american_indian, other
            $table->string('doc_type', 50)->default('income');
            $table->string('title', 150); // e.g. "Formulario W-2 / Taxes 2025" or "I-766 EAD / Green Card"

            // 90-Day Marketplace Dates
            $table->date('notice_date'); // Date of Healthcare.gov eligibility notice
            $table->date('deadline_date'); // notice_date + 90 calendar days

            // Status in Marketplace pipeline
            // pending_upload, uploaded_to_marketplace, verified_by_cms, rejected
            $table->string('status', 40)->default('pending_upload');

            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 150)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_dmi_documents');
    }
};
