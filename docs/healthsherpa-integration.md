# Piloto HealthSherpaONE — SegurosCRM

Actualizado: 15 de octubre de 2026. Prioridad de producto: cotización ACA con datos reales, seguida de inscripción asistida y consulta de estados, según los accesos aprobados. El titular todavía no tiene cuenta de HealthSherpa. No hay conexión verificada ni despliegue de este conector.

## Alcance y orden de entrega

| Etapa | Entregable | Condición para avanzar |
|---|---|---|
| 1. Base técnica | Backend de condados y cotizaciones, validación, permisos y errores seguros | Implementado y probado con respuestas simuladas; pendiente revisión y despliegue |
| 2. Cotizador en pantalla | Formulario revisable, selección explícita del condado, comparación de planes y procedencia/fecha de la cotización | Cuenta ONE, clave activada y confirmación del uso permitido por HealthSherpa |
| 3. Inscripción asistida | Vinculación del agente y apertura del flujo alojado por HS | Acceso de inscripción aprobado, identidad/licencias del agente y autorizaciones correspondientes |
| 4. Seguimiento | Consulta de estados con origen y fecha de actualización | Acceso específico aprobado; tratar datos incompletos y disponibilidad alpha |

Las próximas iteraciones se concentran en el cotizador y su piloto. La fecha del piloto depende del acceso y de la validación con HS; no se promete un plazo de aprobación externa. Se posponen nuevas funciones de IA, Medicare, venta fuera del Marketplace y ampliaciones de administración SaaS que no sean necesarias para este recorrido.

## Qué necesita hacer el titular

1. Iniciar el alta en [HealthSherpaONE](https://one.healthsherpa.com/docs/getting-started/onboarding.html), verificar su correo y revisar/aceptar los acuerdos que correspondan como titular.
2. Explicar que SegurosCRM es un SaaS con una instancia por agencia y solicitar confirmación del modelo permitido, responsabilidades y acuerdos para integrar a varias agencias/agentes. No asumir que una cuenta de agente concede derechos de integración como proveedor tecnológico.
3. Confirmar activación de la clave, permisos, costos, límites y entorno autorizado para pruebas. La documentación pública presenta diferencias sobre staging: no asumir que existe un sandbox público disponible para todos.
4. Para la fase posterior, solicitar acceso a enrollment sessions y policy status, y confirmar el proceso de vinculación de cada agente. Una clave de cotización no concede automáticamente estos accesos.

No compartir claves por chat. La clave se instalará como secreto del servidor. No se compartirán cuentas entre agencias ni se enviarán datos de clientes durante las pruebas simuladas.

Texto listo para solicitar orientación a HS, sin enviar automáticamente:

> We are developing SegurosCRM, a CRM for US health insurance agencies based on Krayin/Laravel. Our initial SaaS deployment model is one instance per agency. We would like to start with ACA on-exchange medical quoting, then add agent-assisted enrollment through HealthSherpa-hosted sessions and, if approved, policy status lookup. Please confirm the permitted integration model for a technology vendor serving multiple agencies, account and agent-linking requirements, necessary agreements and authorizations, API scopes, pricing/rate limits, and the testing environment and test data you authorize. We do not yet have a HealthSherpaONE account or API credentials.

## Base implementada en esta rama

- Nuevas rutas administrativas autenticadas: `GET /admin/insurance/healthsherpa/readiness`, `GET /admin/insurance/healthsherpa/counties?zip_code=...`, `POST /admin/leads/{leadId}/healthsherpa/quotes`. El prefijo admin es configurable.
- Permiso específico `leads.healthsherpa`; las cotizaciones también verifican acceso al expediente y un consentimiento firmado registrado en el CRM. Este registro no demuestra por sí solo que se cumplen las autorizaciones de HS o del Marketplace.
- Datos explícitos: año, ZIP, condado FIPS, estado, tamaño del hogar fiscal, ingresos y solicitantes con edad/parentesco/tabaco. Los identificadores deben pertenecer al expediente; no se inventan datos faltantes. El tamaño fiscal puede ser mayor que la cantidad de solicitantes.
- API fija `https://api.one.healthsherpa.com`, `x-api-key` solo desde servidor, sin seguir redirecciones, con límites locales y respeto de `Retry-After`; sin reintentos automáticos ni exposición de errores privados del proveedor.
- `HEALTHSHERPA_ENABLED=false` y `HEALTHSHERPA_API_KEY=` por defecto. Configuración lista no equivale a conexión verificada ni a permisos aprobados. No hay interfaz de cotización todavía.
- Se bloquea el puente antiguo que fabricaba valores de ZIP, ingresos, fecha de nacimiento y NPN. Sus rutas devuelven mensajes explícitos de indisponibilidad; no realizan inscripción.
- No se guardan las cotizaciones ni se crean pólizas con estas llamadas. Ninguna cotización demuestra inscripción, pago o cobertura activa.

Validación: suite PHP completa en base aislada, **181 pruebas / 1012 aserciones**, y Pint aplicado. Los casos incluyen permisos, miembros ajenos, consentimiento, datos incompletos, errores, límites, redirecciones y transporte. Todas las respuestas de HS fueron simuladas; no se validó una cuenta real ni el recorrido visual.

## Siguiente desarrollo

Construir la pantalla del cotizador con confirmación de datos y selección del condado cuando un ZIP tenga varias opciones. Separar hogar fiscal de personas que solicitan cobertura; mostrar planes, primas y ayudas según la respuesta autorizada de HS, con fecha/origen y sin presentar estimaciones como elegibilidad definitiva.

Antes de un piloto con clientes: corregir la identidad del agente/NPN y nombre de agencia hoy fijados en el consentimiento antiguo; distinguir consentimiento del CRM, autorización de HS y revisión de la solicitud; configurar dominio/HTTPS y validar accesos y protección de los expedientes. Conservar respaldos y verificar recuperación. El respaldo local actual no sustituye una copia fuera del servidor.

Después de aprobación, añadir inscripción asistida mediante el enlace alojado que entregue HS. No implementar envío directo de solicitudes ACA ni automatizar el inicio de sesión del agente. Para seguimiento, usar las consultas autorizadas y conservar estado/origen/fecha; un dato ausente o fallo del proveedor nunca cambia una póliza a activa. No diseñar webhooks de HS sin una interfaz oficialmente habilitada.

## Fuentes del contrato

- [OpenAPI oficial](https://one.healthsherpa.com/openapi.json) y [referencia API](https://one.healthsherpa.com/docs/api-reference.html).
- [Compatibilidad](https://one.healthsherpa.com/docs/integration-guide/compatibility.html).
- [Consideraciones de cumplimiento](https://one.healthsherpa.com/docs/integration-guide/compliance-considerations.html) y [seguridad de integración](https://one.healthsherpa.com/legal/integration-security.html).

Reconfirmar con HS el contrato y los permisos antes de activar tráfico real. Esta hoja de ruta no acredita aprobación de HS, CMS ni aseguradoras.
