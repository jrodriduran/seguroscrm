<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ready-made templates and sequences (paused) so agencies start from good
 * examples: they review the texts, assign stages and turn them on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $ids = [];

        foreach ($this->templates() as $code => $template) {
            $ids[$code] = DB::table('communication_templates')->insertGetId([
                'code' => $code,
                'name' => $template['name'],
                'category' => $template['category'],
                'purpose' => $template['purpose'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($template['contents'] as [$channel, $locale, $subject, $body]) {
                DB::table('communication_template_contents')->insert([
                    'template_id' => $ids[$code],
                    'channel' => $channel,
                    'locale' => $locale,
                    'subject' => $subject,
                    'body' => $body,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($this->sequences() as $code => $sequence) {
            $sequenceId = DB::table('communication_sequences')->insertGetId([
                'code' => $code,
                'name' => $sequence['name'],
                'description' => $sequence['description'],
                'pause_on_reply' => true,
                'exit_on_stage_change' => true,
                'allow_reentry' => $code === 'grace_period',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($sequence['steps'] as $position => $step) {
                DB::table('communication_sequence_steps')->insert([
                    'sequence_id' => $sequenceId,
                    'position' => $position + 1,
                    'type' => $step['type'],
                    'delay_days' => $step['days'] ?? 0,
                    'send_hour' => $step['hour'] ?? null,
                    'template_id' => isset($step['template']) ? $ids[$step['template']] : null,
                    'condition' => $step['condition'] ?? 'always',
                    'config' => isset($step['config']) ? json_encode($step['config']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($sequence['trigger']) {
                DB::table('communication_triggers')->insert([
                    'sequence_id' => $sequenceId,
                    'event' => $sequence['trigger'][0],
                    'config' => json_encode($sequence['trigger'][1]),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Roles that edit contacts may also enroll them in sequences.
        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (in_array('contacts.persons.edit', $permissions, true) && ! in_array('contacts.persons.sequences', $permissions, true)) {
                $permissions[] = 'contacts.persons.sequences';

                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('communication_sequences')->whereIn('code', array_keys($this->sequences()))->delete();
        DB::table('communication_templates')->whereIn('code', array_keys($this->templates()))->delete();

        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'permissions']) as $role) {
            $permissions = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], ['contacts.persons.sequences']));

            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }

    protected function sequences(): array
    {
        return [
            'signed_kit' => [
                'name' => 'Kit "Firmaste"',
                'description' => 'Al firmar: todos los medios de contacto, tarjeta de bienvenida y llamada de bienvenida. Asígnalo a la etapa de firma en Configuración › Comunicaciones por etapa.',
                'trigger' => null,
                'steps' => [
                    ['type' => 'preferred', 'template' => 'signed_contacts'],
                    ['type' => 'physical', 'config' => ['item' => 'card', 'assign_to' => 'master', 'due_hours' => 48, 'note' => 'Tarjeta escrita a mano, firmada por {agent_name}.']],
                    ['type' => 'call_task', 'days' => 2, 'config' => ['title' => 'Llamada de bienvenida a {full_name}', 'assign_to' => 'owner', 'due_hours' => 24, 'note' => 'Confirmar que recibió los medios de contacto y resolver dudas.']],
                ],
            ],
            'policy_welcome' => [
                'name' => 'Bienvenida a la póliza',
                'description' => 'Cuando la póliza queda efectiva: bienvenida, beneficios, cómo pedir cita médica y llamada de control.',
                'trigger' => ['policy_effectuated', []],
                'steps' => [
                    ['type' => 'preferred', 'template' => 'policy_welcome', 'hour' => 10],
                    ['type' => 'email', 'template' => 'policy_benefits', 'days' => 15, 'hour' => 10],
                    ['type' => 'email', 'template' => 'medical_appointment', 'days' => 15, 'hour' => 10],
                    ['type' => 'call_task', 'days' => 15, 'config' => ['title' => 'Llamada de control a {full_name} (45 días)', 'assign_to' => 'owner', 'due_hours' => 48]],
                ],
            ],
            'retention' => [
                'name' => 'Retención: no quiere renovar',
                'description' => 'Cuando una llamada termina en "No renovará": aviso urgente al dueño de la agencia, llamada en 24 h y opciones al cliente.',
                'trigger' => ['call_outcome', ['outcome' => 'not_renewing']],
                'steps' => [
                    ['type' => 'notify', 'config' => ['assign_to' => 'master', 'priority' => 'urgent', 'note' => '{full_name} dice que no renovará. Hay que llamarle hoy.']],
                    ['type' => 'call_task', 'config' => ['title' => 'Retención: llamar a {full_name}', 'assign_to' => 'owner', 'priority' => 'urgent', 'due_hours' => 24]],
                    ['type' => 'preferred', 'template' => 'retention_options', 'days' => 1, 'hour' => 10, 'condition' => 'if_no_reply'],
                    ['type' => 'call_task', 'days' => 3, 'condition' => 'if_no_reply', 'config' => ['title' => 'Retención: segundo intento con {full_name}', 'assign_to' => 'owner', 'due_hours' => 24]],
                ],
            ],
            'renewal' => [
                'name' => 'Renovación',
                'description' => '60 días antes de la renovación: aviso por email, recordatorio por su canal preferido y llamada.',
                'trigger' => ['renewal_before', ['days' => 60]],
                'steps' => [
                    ['type' => 'email', 'template' => 'renewal_reminder', 'hour' => 10],
                    ['type' => 'preferred', 'template' => 'renewal_reminder', 'days' => 15, 'hour' => 10, 'condition' => 'if_no_reply'],
                    ['type' => 'call_task', 'days' => 15, 'config' => ['title' => 'Renovación de {full_name}: revisar plan', 'assign_to' => 'owner', 'due_hours' => 72]],
                ],
            ],
            'birthday' => [
                'name' => 'Cumpleaños',
                'description' => 'Felicitación el día del cumpleaños por el canal preferido del cliente.',
                'trigger' => ['birthday', []],
                'steps' => [
                    ['type' => 'preferred', 'template' => 'birthday', 'hour' => 10],
                ],
            ],
            'grace_period' => [
                'name' => 'Período de gracia',
                'description' => 'Cuando la póliza entra en período de gracia: aviso al cliente, tarea urgente y recordatorio a los 5 días.',
                'trigger' => ['policy_status', ['status' => 'grace_period_1']],
                'steps' => [
                    ['type' => 'preferred', 'template' => 'grace_period', 'hour' => 10],
                    ['type' => 'call_task', 'config' => ['title' => 'Pago pendiente: llamar a {full_name}', 'assign_to' => 'owner', 'priority' => 'urgent', 'due_hours' => 24]],
                    ['type' => 'preferred', 'template' => 'grace_period', 'days' => 5, 'hour' => 10, 'condition' => 'if_no_reply'],
                ],
            ],
        ];
    }

    protected function templates(): array
    {
        return [
            'signed_contacts' => [
                'name' => 'Gracias por confiar — medios de contacto',
                'category' => 'welcome',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', '{first_name}, gracias por confiar en {agency_name}', "Hola {first_name}:\n\nGracias por confiar en nosotros. Tu solicitud ya está en proceso y te acompañaremos en cada paso.\n\n**Cómo contactarnos**\n• Tu agente: {agent_name} — {agent_email}\n• Teléfono: {agency_phone}\n• WhatsApp: {agency_whatsapp}\n• Web: {agency_website}\n\nGuarda estos datos: ante cualquier duda sobre tu seguro, escríbenos antes de llamar a la aseguradora.\n\nUn abrazo,\n{agent_name}"],
                    ['email', 'en', '{first_name}, thank you for trusting {agency_name}', "Hi {first_name},\n\nThank you for trusting us. Your application is being processed and we will be with you every step of the way.\n\n**How to reach us**\n• Your agent: {agent_name} — {agent_email}\n• Phone: {agency_phone}\n• WhatsApp: {agency_whatsapp}\n• Web: {agency_website}\n\nKeep this handy: whenever you have a question about your coverage, contact us before calling the carrier.\n\nBest,\n{agent_name}"],
                    ['whatsapp', 'es', null, 'Hola {first_name} 👋 Gracias por confiar en {agency_name}. Tu solicitud está en proceso. Guarda este número: aquí te atendemos. Tu agente es {agent_name} y nuestra oficina es el {agency_phone}.'],
                    ['whatsapp', 'en', null, "Hi {first_name} 👋 Thank you for trusting {agency_name}. Your application is in process. Save this number — we're here for you. Your agent is {agent_name}; our office is {agency_phone}."],
                ],
            ],
            'policy_welcome' => [
                'name' => 'Bienvenida: tu póliza está activa',
                'category' => 'welcome',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', '¡Bienvenido, {first_name}! Tu póliza ya está activa', "Hola {first_name}:\n\n¡Buenas noticias! Tu póliza **{policy_number}** con **{carrier}** ({plan}) está activa desde el {effective_date}.\n\nEn los próximos días te enviaremos información sobre tus beneficios y cómo usar tu seguro.\n\nSi necesitas algo, responde este correo o escríbenos por WhatsApp al {agency_whatsapp}.\n\n{agent_name}\n{agency_name}"],
                    ['email', 'en', 'Welcome, {first_name}! Your policy is active', "Hi {first_name},\n\nGreat news! Your policy **{policy_number}** with **{carrier}** ({plan}) is active as of {effective_date}.\n\nOver the next few days we will send you information about your benefits and how to use your coverage.\n\nIf you need anything, reply to this email or message us on WhatsApp at {agency_whatsapp}.\n\n{agent_name}\n{agency_name}"],
                    ['whatsapp', 'es', null, '¡{first_name}, tu póliza con {carrier} ya está activa desde el {effective_date}! 🎉 Cualquier duda, escríbenos por aquí. — {agent_name}, {agency_name}'],
                    ['whatsapp', 'en', null, '{first_name}, your {carrier} policy is active as of {effective_date}! 🎉 Any questions, message us here. — {agent_name}, {agency_name}'],
                ],
            ],
            'policy_benefits' => [
                'name' => 'Los beneficios de tu póliza',
                'category' => 'benefits',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', '{first_name}, conoce los beneficios de tu plan', "Hola {first_name}:\n\nQueremos que aproveches tu plan **{plan}** al máximo:\n\n• **Chequeo preventivo anual** sin costo con tu médico de cabecera.\n• **Vacunas y exámenes preventivos** cubiertos.\n• **Telemedicina** para consultas rápidas desde tu teléfono.\n• **Medicamentos** con copago reducido en farmacias de la red.\n\nRevisa los detalles exactos en la tarjeta y el portal de {carrier}. Si tienes dudas sobre qué cubre tu plan, te ayudamos.\n\n{agent_name}"],
                    ['email', 'en', '{first_name}, get to know your plan benefits', "Hi {first_name},\n\nWe want you to get the most out of your **{plan}** plan:\n\n• **Annual preventive checkup** at no cost with your primary care doctor.\n• **Vaccines and preventive screenings** covered.\n• **Telehealth** for quick visits from your phone.\n• **Prescriptions** with lower copays at in-network pharmacies.\n\nCheck the exact details on your {carrier} card and portal. If you are unsure what your plan covers, we can help.\n\n{agent_name}"],
                ],
            ],
            'medical_appointment' => [
                'name' => 'Cómo pedir tu cita médica',
                'category' => 'care',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', 'Cómo pedir tu primera cita médica', "Hola {first_name}:\n\nPedir tu cita es fácil:\n\n1. **Elige tu médico de cabecera (PCP)** en el directorio de {carrier} o dinos y lo buscamos contigo.\n2. **Llama al consultorio** y di que tienes {carrier}, plan {plan}. Ten a mano tu número de miembro.\n3. **Lleva tu tarjeta y una identificación** el día de la cita.\n\n¿Prefieres que te ayudemos? Escríbenos al {agency_whatsapp} y lo hacemos juntos.\n\n{agent_name}"],
                    ['email', 'en', 'How to book your first doctor visit', "Hi {first_name},\n\nBooking your visit is easy:\n\n1. **Choose your primary care doctor (PCP)** in the {carrier} directory, or tell us and we will find one with you.\n2. **Call the office** and say you have {carrier}, plan {plan}. Keep your member ID handy.\n3. **Bring your card and an ID** to the appointment.\n\nWould you like help? Message us at {agency_whatsapp} and we will do it together.\n\n{agent_name}"],
                ],
            ],
            'renewal_reminder' => [
                'name' => 'Tu renovación se acerca',
                'category' => 'renewal',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', '{first_name}, tu renovación es el {renewal_date}', "Hola {first_name}:\n\nTu póliza con {carrier} se renueva el **{renewal_date}**. Es el mejor momento para revisar si tu plan sigue siendo el adecuado: cambios de ingresos, familia o médicos pueden darte un mejor precio o más beneficios.\n\nResponde este correo o escríbenos al {agency_whatsapp} y lo revisamos juntos en 10 minutos.\n\n{agent_name}"],
                    ['email', 'en', '{first_name}, your renewal is on {renewal_date}', "Hi {first_name},\n\nYour {carrier} policy renews on **{renewal_date}**. It is the best time to check that your plan still fits: changes in income, family or doctors may get you a better price or more benefits.\n\nReply to this email or message us at {agency_whatsapp} and we will review it together in 10 minutes.\n\n{agent_name}"],
                    ['whatsapp', 'es', null, 'Hola {first_name}, tu póliza con {carrier} se renueva el {renewal_date}. ¿Revisamos juntos si tu plan sigue siendo el mejor para ti? Solo toma 10 minutos. — {agent_name}'],
                    ['whatsapp', 'en', null, 'Hi {first_name}, your {carrier} policy renews on {renewal_date}. Shall we check together that your plan is still the best fit? It only takes 10 minutes. — {agent_name}'],
                ],
            ],
            'retention_options' => [
                'name' => 'Revisemos tus opciones',
                'category' => 'retention',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', '{first_name}, revisemos tus opciones antes de decidir', "Hola {first_name}:\n\nEntendemos que estás pensando no renovar. Antes de decidir, déjanos revisar contigo otras opciones: a veces un cambio de plan o de aseguradora baja el costo sin perder tus médicos.\n\n¿Te llamamos hoy? Responde con el mejor horario o escríbenos al {agency_whatsapp}.\n\n{agent_name}"],
                    ['email', 'en', '{first_name}, let us review your options before you decide', "Hi {first_name},\n\nWe understand you are thinking about not renewing. Before you decide, let us review other options with you: sometimes a different plan or carrier lowers the cost without losing your doctors.\n\nShall we call you today? Reply with the best time or message us at {agency_whatsapp}.\n\n{agent_name}"],
                    ['whatsapp', 'es', null, 'Hola {first_name}, entendemos que estás pensando no renovar. ¿Nos das 10 minutos para mostrarte otras opciones? A veces se puede pagar menos sin perder a tus médicos. — {agent_name}'],
                    ['whatsapp', 'en', null, 'Hi {first_name}, we understand you are thinking about not renewing. Could you give us 10 minutes to show you other options? You may pay less and keep your doctors. — {agent_name}'],
                ],
            ],
            'birthday' => [
                'name' => 'Feliz cumpleaños',
                'category' => 'greeting',
                'purpose' => 'marketing',
                'contents' => [
                    ['email', 'es', '¡Feliz cumpleaños, {first_name}! 🎂', "¡Feliz cumpleaños, {first_name}!\n\nTodo el equipo de {agency_name} te desea un año lleno de salud y alegría. Gracias por dejarnos cuidar de ti y de tu familia.\n\nCon cariño,\n{agent_name}"],
                    ['email', 'en', 'Happy birthday, {first_name}! 🎂', "Happy birthday, {first_name}!\n\nEveryone at {agency_name} wishes you a year full of health and joy. Thank you for letting us take care of you and your family.\n\nWarm wishes,\n{agent_name}"],
                    ['whatsapp', 'es', null, '¡Feliz cumpleaños, {first_name}! 🎂🎉 Todo el equipo de {agency_name} te desea un año lleno de salud y alegría. — {agent_name}'],
                    ['whatsapp', 'en', null, 'Happy birthday, {first_name}! 🎂🎉 Everyone at {agency_name} wishes you a year full of health and joy. — {agent_name}'],
                ],
            ],
            'grace_period' => [
                'name' => 'Tu pago está pendiente',
                'category' => 'payment',
                'purpose' => 'transactional',
                'contents' => [
                    ['email', 'es', 'Importante: tu pago con {carrier} está pendiente', "Hola {first_name}:\n\nTu póliza **{policy_number}** con {carrier} tiene un pago pendiente y entró en período de gracia. Para no perder la cobertura, realiza el pago lo antes posible.\n\n¿Necesitas ayuda o quieres revisar opciones? Escríbenos al {agency_whatsapp} o llámanos al {agency_phone}.\n\n{agent_name}"],
                    ['email', 'en', 'Important: your {carrier} payment is pending', "Hi {first_name},\n\nYour policy **{policy_number}** with {carrier} has a pending payment and is in its grace period. To keep your coverage, please make the payment as soon as possible.\n\nNeed help or want to review options? Message us at {agency_whatsapp} or call {agency_phone}.\n\n{agent_name}"],
                    ['whatsapp', 'es', null, 'Hola {first_name}, tu póliza con {carrier} tiene un pago pendiente. Para no perder la cobertura, págalo lo antes posible. ¿Te ayudamos? — {agent_name}'],
                    ['whatsapp', 'en', null, 'Hi {first_name}, your {carrier} policy has a pending payment. Please pay soon to keep your coverage. Can we help? — {agent_name}'],
                ],
            ],
        ];
    }
};
