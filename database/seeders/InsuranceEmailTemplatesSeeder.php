<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceEmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $templates = [
            [
                'name'    => 'Insurance: CMS Consent Form Confirmation',
                'subject' => 'Consentimiento para Asesoría e Inscripción de Cobertura Médica - {%leads.title%}',
                'content' => '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; max-width: 650px;">
                    <div style="background-color: #1e40af; color: #ffffff; padding: 18px 24px; border-radius: 6px 6px 0 0;">
                        <h2 style="margin: 0; font-size: 18px;">Registro de Consentimiento de Asesoría de Seguros</h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Conformidad con las Normativas Federales de CMS / Marketplace</p>
                    </div>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 6px 6px;">
                        <p>Estimado(a) <strong>{%leads.person_name%}</strong>,</p>
                        <p>En cumplimiento con las directrices federales de los Centros de Servicios de Medicare y Medicaid (<strong>CMS</strong>), confirmamos que usted ha autorizado a nuestro agente de seguros con licencia para brindarle asistencia en:</p>
                        <ul style="padding-left: 20px; color: #334155;">
                            <li>Búsqueda y comparación de planes de salud y vida disponibles para su código postal.</li>
                            <li>Determinación de elegibilidad para el Crédito Fiscal para Primas (Subsidio APTC) en el Mercado de Seguros (HealthCare.gov / State Exchange).</li>
                            <li>Llenado, envío y firma de la solicitud oficial de inscripción con la aseguradora seleccionada.</li>
                        </ul>
                        <div style="background-color: #e0e7ff; border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 20px 0; border-radius: 0 4px 4px 0;">
                            <p style="margin: 0; font-size: 12px; color: #1e3a8a;">
                                <strong>Nota de Seguridad:</strong> Sus datos personales y fiscales son estrictamente confidenciales y protegidos bajo estándares federales de privacidad. Si desea revocar este consentimiento o actualizar su información, puede comunicarse con nosotros en cualquier momento.
                            </p>
                        </div>
                        <p style="margin-bottom: 0;">Atentamente,</p>
                        <p style="margin-top: 4px; font-weight: bold; color: #1e40af;">Su Equipo de Asesores de Seguros</p>
                    </div>
                </div>',
            ],
            [
                'name'    => 'Insurance: Eligibility Documents Request (DMI)',
                'subject' => 'Documentos Requeridos para Validar su Subsidio Médico - {%leads.title%}',
                'content' => '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; max-width: 650px;">
                    <div style="background-color: #d97706; color: #ffffff; padding: 18px 24px; border-radius: 6px 6px 0 0;">
                        <h2 style="margin: 0; font-size: 18px;">Documentos Pendientes para su Cobertura Médica</h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Validación Requerida por el Mercado de Seguros de Salud (DMI)</p>
                    </div>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 6px 6px;">
                        <p>Estimado(a) <strong>{%leads.person_name%}</strong>,</p>
                        <p>Para asegurar que su subsidio federal de salud (APTC) y su póliza permanezcan activos sin interrupciones, el Mercado de Seguros requiere que presentemos comprobantes de elegibilidad antes de la fecha límite.</p>
                        <p>Por favor responda a este correo adjuntando copias o fotos legibles de los siguientes documentos:</p>
                        <div style="background-color: #ffffff; border: 1px solid #cbd5e1; padding: 16px; border-radius: 6px; margin: 16px 0;">
                            <p style="margin: 0 0 8px 0; font-weight: bold; color: #0f172a;">Documentación Solicitada:</p>
                            <ul style="margin: 0; padding-left: 20px; color: #334155;">
                                <li><strong>Prueba de Ingresos:</strong> Última declaración de impuestos (Formulario 1040), W-2, o los 4 talones de pago (paystubs) más recientes.</li>
                                <li><strong>Estatus Migratorio / Ciudadanía:</strong> Tarjeta de Residencia Permanente (Green Card), Permiso de Trabajo (EAD), o Certificado de Naturalización.</li>
                                <li><strong>Identificación con Foto:</strong> Licencia de conducir o ID estatal vigente.</li>
                            </ul>
                        </div>
                        <p>Tan pronto recibamos sus documentos, nuestro equipo los cargará directamente en el portal oficial y le confirmará la resolución.</p>
                        <p style="margin-bottom: 0;">Muchas gracias por su colaboración,</p>
                        <p style="margin-top: 4px; font-weight: bold; color: #d97706;">Departamento de Procesamiento y Documentos</p>
                    </div>
                </div>',
            ],
            [
                'name'    => 'Insurance: Policy Issued & Welcome Packet',
                'subject' => '¡Felicitaciones! Su Póliza de Seguro ha sido Emitida con Éxito - {%leads.title%}',
                'content' => '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; max-width: 650px;">
                    <div style="background-color: #059669; color: #ffffff; padding: 18px 24px; border-radius: 6px 6px 0 0;">
                        <h2 style="margin: 0; font-size: 18px;">¡Bienvenido(a)! Su Póliza está Activa y Vigente</h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Confirmación de Cobertura y Próximos Pasos</p>
                    </div>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 6px 6px;">
                        <p>Estimado(a) <strong>{%leads.person_name%}</strong>,</p>
                        <p>Nos complace informarle que su solicitud de cobertura ha sido formalmente aprobada y su póliza ha sido <strong>emitida con éxito</strong>.</p>
                        <div style="background-color: #ffffff; border: 1px solid #a7f3d0; padding: 16px; border-radius: 6px; margin: 18px 0;">
                            <p style="margin: 0 0 8px 0; font-weight: bold; color: #065f46; font-size: 15px;">Detalles de su Cobertura:</p>
                            <p style="margin: 3px 0;">&bull; <strong>Titular:</strong> {%leads.person_name%}</p>
                            <p style="margin: 3px 0;">&bull; <strong>Caso / Referencia:</strong> {%leads.title%}</p>
                        </div>
                        <h4 style="color: #065f46; margin: 18px 0 8px 0;">¿Qué sucede ahora?</h4>
                        <ol style="padding-left: 20px; color: #334155;">
                            <li><strong>Tarjetas de Seguro (Member ID Cards):</strong> La aseguradora enviará sus tarjetas físicas por correo postal a su dirección en los próximos 7 a 14 días hábiles.</li>
                            <li><strong>Portal Web de la Aseguradora:</strong> Podrá crear su cuenta en el sitio web oficial de la compañía aseguradora para descargar tarjetas digitales y consultar su red médica.</li>
                            <li><strong>Asignación de Médico Primario (PCP):</strong> Recuerde verificar que su médico primario preferido esté en la red del plan.</li>
                        </ol>
                        <p style="margin-top: 16px;">Estamos aquí para apoyarle durante todo el año con consultas, cambios y reclamos.</p>
                        <p style="margin-bottom: 0;">¡Gracias por confiar en nuestra agencia!</p>
                        <p style="margin-top: 4px; font-weight: bold; color: #059669;">Su Agente de Seguros de Confianza</p>
                    </div>
                </div>',
            ],
            [
                'name'    => 'Insurance: Annual Renewal Reminder (OEP / AEP)',
                'subject' => 'Aviso Importante: Periodo de Renovación de su Seguro de Salud - {%leads.title%}',
                'content' => '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; max-width: 650px;">
                    <div style="background-color: #7c3aed; color: #ffffff; padding: 18px 24px; border-radius: 6px 6px 0 0;">
                        <h2 style="margin: 0; font-size: 18px;">Periodo de Inscripción Abierta y Renovación Anual</h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Revise sus Beneficios y Proteja su Subsidio para el Nuevo Año</p>
                    </div>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 6px 6px;">
                        <p>Estimado(a) <strong>{%leads.person_name%}</strong>,</p>
                        <p>Le escribimos para recordarle que ha comenzado el <strong>Periodo Oficial de Inscripción Abierta</strong> para su cobertura médica.</p>
                        <p>Las compañías de seguros actualizan anualmente sus primas, listas de medicamentos cubiertos y redes de médicos. Por esta razón, es fundamental revisar su caso para asegurar:</p>
                        <ul style="padding-left: 20px; color: #334155;">
                            <li>Que sus ingresos estimados estén actualizados para no perder subsidios ni tener ajustes en impuestos.</li>
                            <li>Que sus médicos y hospitales habituales continúen en la red de su plan.</li>
                            <li>Si existen planes nuevos con mejores beneficios o primas más bajas ($0/mes) para su familia.</li>
                        </ul>
                        <div style="text-align: center; margin: 24px 0;">
                            <div style="display: inline-block; background-color: #7c3aed; color: #ffffff; padding: 12px 24px; border-radius: 6px; font-weight: bold; text-decoration: none;">
                                Contáctenos para agendar su revisión anual gratuita
                            </div>
                        </div>
                        <p style="margin-bottom: 0;">Quedamos a su entera disposición,</p>
                        <p style="margin-top: 4px; font-weight: bold; color: #7c3aed;">Servicio al Cliente y Renovaciones</p>
                    </div>
                </div>',
            ],
        ];

        foreach ($templates as $t) {
            $existing = DB::table('email_templates')
                ->where('name', $t['name'])
                ->first();

            if ($existing) {
                DB::table('email_templates')
                    ->where('id', $existing->id)
                    ->update([
                        'subject'    => $t['subject'],
                        'content'    => $t['content'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('email_templates')->insert([
                    'name'       => $t['name'],
                    'subject'    => $t['subject'],
                    'content'    => $t['content'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}