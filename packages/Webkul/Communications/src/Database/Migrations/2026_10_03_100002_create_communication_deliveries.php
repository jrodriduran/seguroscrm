<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Physical deliveries to clients (cards, gift cards, welcome kits…):
         * requested by a sequence or by hand, approved by the agency owner
         * when they cost money, then bought, sent and delivered.
         */
        Schema::create('communication_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->unsignedBigInteger('follow_up_id')->nullable();
            $table->string('item', 20);
            $table->string('note', 500)->nullable();
            $table->json('address')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('status', 12)->default('requested')->index();
            $table->string('tracking', 120)->nullable();
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'created_at']);
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });

        // Those who edit contacts request deliveries; reception processes them.
        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'name', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if ((in_array('contacts.persons.edit', $permissions, true) || str_contains(mb_strtolower($role->name), 'recep'))
                && ! in_array('mail.communication_deliveries', $permissions, true)) {
                $permissions[] = 'mail.communication_deliveries';
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_deliveries');

        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'permissions']) as $role) {
            $permissions = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], ['mail.communication_deliveries']));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }
};
