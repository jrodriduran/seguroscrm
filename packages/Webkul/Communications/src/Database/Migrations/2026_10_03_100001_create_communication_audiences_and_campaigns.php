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
         * Saved client lists: a set of conditions (all must match), always
         * evaluated live so the list stays current.
         */
        Schema::create('communication_audiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('code', 60)->nullable();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->json('rules');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        /**
         * One message to a list, on a date. Recipients are frozen when it is
         * scheduled, then sent a few at a time within sending hours.
         */
        Schema::create('communication_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->nullable()->index();
            $table->string('name');
            $table->foreignId('audience_id')->nullable()->constrained('communication_audiences')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->string('channel', 15)->default('preferred');
            $table->string('occasion', 30)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status', 12)->default('draft')->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('communication_campaigns')->cascadeOnDelete();
            $table->unsignedInteger('person_id');
            $table->string('status', 10)->default('pending');
            $table->string('channel', 15)->nullable();
            $table->string('detail', 500)->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->unique(['campaign_id', 'person_id'], 'cm_campaign_recipient_unique');
            $table->index(['campaign_id', 'status'], 'cm_campaign_recipient_status');
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
        });

        $this->seed();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_campaign_recipients');
        Schema::dropIfExists('communication_campaigns');
        Schema::dropIfExists('communication_audiences');

        DB::table('communication_templates')->whereIn('code', array_keys($this->templates()))->delete();
    }

    /**
     * Lists and greeting templates for the special dates.
     */
    protected function seed(): void
    {
        $now = now();

        $audiences = [
            'mothers' => ['Madres (mujeres con hijos)', [['field' => 'gender', 'value' => 'Female'], ['field' => 'has_children', 'value' => 'yes']]],
            'fathers' => ['Padres (hombres con hijos)', [['field' => 'gender', 'value' => 'Male'], ['field' => 'has_children', 'value' => 'yes']]],
            'active_clients' => ['Clientes con póliza activa', [['field' => 'policy_status', 'value' => 'active']]],
            'all_contacts' => ['Todos los contactos', []],
            'birthdays_month' => ['Cumpleaños de este mes', [['field' => 'birthday_month', 'value' => 'current']]],
        ];

        foreach ($audiences as $code => [$name, $rules]) {
            DB::table('communication_audiences')->insert([
                'code' => $code,
                'name' => $name,
                'rules' => json_encode($rules),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->templates() as $code => [$name, $contents]) {
            $id = DB::table('communication_templates')->insertGetId([
                'code' => $code,
                'name' => $name,
                'category' => 'greeting',
                'purpose' => 'marketing',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($contents as [$channel, $locale, $subject, $body]) {
                DB::table('communication_template_contents')->insert([
                    'template_id' => $id, 'channel' => $channel, 'locale' => $locale,
                    'subject' => $subject, 'body' => $body, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    protected function templates(): array
    {
        return [
            'mothers_day' => ['Día de las Madres', [
                ['email', 'es', '¡Feliz Día de las Madres, {first_name}! 💐', "¡Feliz Día de las Madres, {first_name}!\n\nGracias por todo lo que haces por tu familia. En {agency_name} nos honra cuidar de ti y de los tuyos.\n\nQue hoy te consientan como mereces.\n\nCon cariño,\n{agent_name}"],
                ['email', 'en', "Happy Mother's Day, {first_name}! 💐", "Happy Mother's Day, {first_name}!\n\nThank you for everything you do for your family. At {agency_name} we are honored to take care of you and yours.\n\nWe hope you are spoiled today as you deserve.\n\nWarm wishes,\n{agent_name}"],
                ['whatsapp', 'es', null, '¡Feliz Día de las Madres, {first_name}! 💐 Gracias por todo lo que haces por tu familia. Un abrazo de todo el equipo de {agency_name}.'],
                ['whatsapp', 'en', null, "Happy Mother's Day, {first_name}! 💐 Thank you for all you do for your family. A big hug from everyone at {agency_name}."],
            ]],
            'fathers_day' => ['Día del Padre', [
                ['email', 'es', '¡Feliz Día del Padre, {first_name}! 👔', "¡Feliz Día del Padre, {first_name}!\n\nGracias por ser el pilar de tu familia. En {agency_name} nos alegra acompañarte cuidando de los tuyos.\n\nQue disfrutes este día.\n\n{agent_name}"],
                ['email', 'en', "Happy Father's Day, {first_name}! 👔", "Happy Father's Day, {first_name}!\n\nThank you for being the pillar of your family. At {agency_name} we are glad to help you take care of yours.\n\nEnjoy your day.\n\n{agent_name}"],
                ['whatsapp', 'es', null, '¡Feliz Día del Padre, {first_name}! 👔 Gracias por cuidar siempre de tu familia. Un abrazo de {agency_name}.'],
                ['whatsapp', 'en', null, "Happy Father's Day, {first_name}! 👔 Thank you for always taking care of your family. Best wishes from {agency_name}."],
            ]],
            'thanksgiving' => ['Acción de Gracias', [
                ['email', 'es', 'Gracias, {first_name} 🦃', "Hola {first_name}:\n\nEn este Día de Acción de Gracias queremos agradecerte por confiar en {agency_name}. Es un privilegio cuidar de ti y de tu familia.\n\n¡Feliz Día de Acción de Gracias!\n\n{agent_name}"],
                ['email', 'en', 'Thank you, {first_name} 🦃', "Hi {first_name},\n\nThis Thanksgiving we want to thank you for trusting {agency_name}. It is a privilege to take care of you and your family.\n\nHappy Thanksgiving!\n\n{agent_name}"],
                ['whatsapp', 'es', null, '¡Feliz Día de Acción de Gracias, {first_name}! 🦃 Gracias por confiar en {agency_name}.'],
                ['whatsapp', 'en', null, 'Happy Thanksgiving, {first_name}! 🦃 Thank you for trusting {agency_name}.'],
            ]],
            'christmas' => ['Navidad', [
                ['email', 'es', '¡Feliz Navidad, {first_name}! 🎄', "¡Feliz Navidad, {first_name}!\n\nTodo el equipo de {agency_name} te desea unas fiestas llenas de paz, salud y alegría junto a los tuyos.\n\nGracias por un año más de confianza.\n\n{agent_name}"],
                ['email', 'en', 'Merry Christmas, {first_name}! 🎄', "Merry Christmas, {first_name}!\n\nEveryone at {agency_name} wishes you a holiday season full of peace, health and joy with your loved ones.\n\nThank you for another year of trust.\n\n{agent_name}"],
                ['whatsapp', 'es', null, '¡Feliz Navidad, {first_name}! 🎄 Que estas fiestas estén llenas de paz y salud. Un abrazo de {agency_name}.'],
                ['whatsapp', 'en', null, 'Merry Christmas, {first_name}! 🎄 Wishing you a season full of peace and health. — {agency_name}'],
            ]],
            'new_year' => ['Año Nuevo', [
                ['email', 'es', '¡Feliz Año Nuevo, {first_name}! 🎉', "¡Feliz Año Nuevo, {first_name}!\n\nQue este nuevo año te traiga salud, prosperidad y muchos momentos felices. En {agency_name} seguiremos cuidando de ti.\n\n{agent_name}"],
                ['email', 'en', 'Happy New Year, {first_name}! 🎉', "Happy New Year, {first_name}!\n\nMay the new year bring you health, prosperity and many happy moments. At {agency_name} we will keep taking care of you.\n\n{agent_name}"],
                ['whatsapp', 'es', null, '¡Feliz Año Nuevo, {first_name}! 🎉 Te deseamos salud y prosperidad. — {agency_name}'],
                ['whatsapp', 'en', null, 'Happy New Year, {first_name}! 🎉 Wishing you health and prosperity. — {agency_name}'],
            ]],
        ];
    }
};
