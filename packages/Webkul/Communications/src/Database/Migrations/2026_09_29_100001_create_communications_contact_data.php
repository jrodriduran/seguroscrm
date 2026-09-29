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
         * How each client wants to be reached.
         */
        Schema::table('persons', function (Blueprint $table) {
            $table->string('preferred_channel', 20)->nullable()->after('job_title');
        });

        /**
         * Consent history per client and channel. Append-only: the latest row
         * of each channel is the current state, older rows are the audit trail.
         */
        Schema::create('contact_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('person_id');
            $table->string('channel', 20);
            $table->string('status', 10);
            $table->string('source', 30);
            $table->string('note', 500)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['person_id', 'channel', 'id']);

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        /**
         * Outcomes an agent picks when logging a call. Each agency can rename,
         * add or pause them; some also withdraw consent ("do not call").
         */
        Schema::create('communication_call_outcomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('name', 80)->nullable();
            $table->string('tone', 10)->default('neutral');
            $table->string('revokes', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['agency_id', 'code']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('outcome', 40)->nullable()->after('type')->index();
        });

        $now = now();

        $defaults = [
            ['interested', 'positive', null],
            ['wants_quote', 'positive', null],
            ['appointment_set', 'positive', null],
            ['info_requested', 'neutral', null],
            ['call_back', 'neutral', null],
            ['voicemail', 'neutral', null],
            ['no_answer', 'neutral', null],
            ['not_renewing', 'negative', null],
            ['not_interested', 'negative', null],
            ['wrong_number', 'negative', null],
            ['do_not_call', 'negative', 'call'],
        ];

        DB::table('communication_call_outcomes')->insert(collect($defaults)->map(fn ($row, $index) => [
            'code' => $row[0],
            'tone' => $row[1],
            'revokes' => $row[2],
            'sort_order' => $index + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        /**
         * Mailing address for clients (cards, welcome kits, gift cards).
         */
        if (! DB::table('attributes')->where('entity_type', 'persons')->where('code', 'address')->exists()) {
            DB::table('attributes')->insert([
                'code' => 'address',
                'name' => 'Mailing Address',
                'type' => 'address',
                'entity_type' => 'persons',
                'sort_order' => 9,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        /**
         * TCPA consent captured on leads covers calls and text messages.
         */
        $consents = DB::table('leads')
            ->whereNotNull('person_id')
            ->where('has_tcpa_consent', 1)
            ->get(['person_id', 'tcpa_consented_at', 'tcpa_consent_type', 'user_id'])
            ->unique('person_id');

        foreach ($consents as $lead) {
            foreach (['call', 'sms'] as $channel) {
                DB::table('contact_consents')->insert([
                    'person_id' => $lead->person_id,
                    'channel' => $channel,
                    'status' => 'granted',
                    'source' => 'tcpa_lead',
                    'note' => $lead->tcpa_consent_type,
                    'user_id' => $lead->user_id,
                    'created_at' => $lead->tcpa_consented_at ?? $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $attributeId = DB::table('attributes')->where('entity_type', 'persons')->where('code', 'address')->value('id');

        if ($attributeId) {
            DB::table('attribute_values')->where('attribute_id', $attributeId)->delete();
            DB::table('attributes')->where('id', $attributeId)->delete();
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['outcome']);
            $table->dropColumn('outcome');
        });

        Schema::dropIfExists('communication_call_outcomes');
        Schema::dropIfExists('contact_consents');

        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn('preferred_channel');
        });
    }
};
