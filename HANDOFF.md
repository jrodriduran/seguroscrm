# SegurosCRM: traspaso del proyecto

Estado al 2026-10-15. Léelo completo antes de tocar código. Complementa a
`AGENTS.md` (convenciones de Krayin) y no lo reemplaza.

**Cierre técnico del piloto (2026-10-16):** ver `deploy/pilot/README.md` para
criterios de aceptación y pendientes reales. `feature/pilot-closure` añade identidad
explícita por agente (`insurance:agent-identity`), protege firmas y alcance de acceso,
corrige Rx/venta cruzada y permite revisar CSV sin importar (`insurance:preview-contacts`).
La identidad configurada no verifica licencias. La suite aislada pasó 250 pruebas
con 1.209 aserciones y todas las vistas compilan. Playwright sigue
en revisión; no considerar el piloto listo ni desplegado por tener commits.

**Prioridad comercial actual (2026-10-16):** primer cliente: una dueña y dos
agentes. El 95% del canal es WhatsApp; no significa 95% de autonomía de IA.
Piloto centrado en inbox compartido, asignación/seguimiento diario y felicitaciones
automáticas de cumpleaños. Objetivo de entrega: fin de semana 17–18 de octubre,
condicionado a conexión oficial WhatsApp, plantillas aprobadas y HTTPS. No activar
envíos reales hasta revisar configuración y autorización de la clienta. El código
actual de MessageDispatcher solo envía texto en conversaciones ya vinculadas: no
implementa aún plantillas de proveedor para iniciar contactos fuera de la ventana
de atención. No presentar el kit de cumpleaños pausado como integración lista.
Pendiente confirmar qué WhatsApp tiene la clienta y si conservará el número.
Nombre/marca, HS, call center e IA de venta quedan detrás de este piloto.

La clienta trabaja ACA y Medicare y actualmente usa WhatsApp Business en el celular,
sin API. El dueño pidió dejar para después la conexión del número y la configuración
externa, y continuar desarrollando. `feature/pilot-readiness` incorpora un panel
interno de revisión de cumpleaños (sin envíos), permiso `communication_birthdays`,
filtros por responsable/fecha y estado del consentimiento WhatsApp, con teléfono
enmascarado. Comparte la consulta de fechas con el programador. No cambia estados
de secuencias ni concede permisos automáticamente; las políticas de cumpleaños
del 29 de febrero siguen siendo estrictas (sin sustitución por otro día).

**Segunda fase:** preparar un piloto de cotización ACA con HealthSherpaONE.
El titular aún no tiene cuenta. La rama `feature/healthsherpa-foundation` contiene
un backend desactivado por defecto y probado con respuestas simuladas; no está
desplegado ni conectado. Primero cotizador y datos revisados, después inscripción
asistida y estados según aprobación. Ver [plan y requisitos](docs/healthsherpa-integration.md).
El puente anterior que inventaba datos queda bloqueado en esa rama. No reactivarlo.

**Grupo familiar:** `feature/household-transitions` añade cambios de cobertura con
motivo, tipo de plan, fechas, tarea asignada y confirmación manual con referencia.
Permite crear un expediente individual vinculado sin copiar consentimiento ni datos
fiscales del titular. Las alertas por 26 años solo solicitan revisión; nunca dan de
baja. No modifica el hogar fiscal, pólizas ni comisiones. Requiere migración y el
permiso `leads.family_transitions` (más `leads.create` para el expediente individual).
Incluye protección de acceso en las rutas familiares heredadas y bloquea el borrado
ordinario de integrantes. No desplegado todavía.

---

## 1. Qué es

**SegurosCRM** es un CRM para agencias de seguros de salud de EE. UU. (ACA,
Medicare, vida). Está construido sobre **Krayin CRM** (Laravel 12, PHP 8.3, MIT).

- El nombre comercial está **pendiente**. "SegurosCRM" es provisional y se
  configura en Configuración → General → Marca (`Webkul\Teamwork\Support\Brand`).
- Se vende como **SaaS: una instancia por agencia** (ver §7).
- La interfaz está en es / en / pt_BR. Los textos de la interfaz se escriben
  siempre en los tres idiomas, con las mismas claves.

## 2. Dónde corre

| Qué | Dónde |
|---|---|
| Servidor de desarrollo/producción actual | `iaprod01` → `192.168.1.51`, usuario ssh `admin1` (LAN, tiene Coolify, n8n y Chatwoot) |
| Código | `/home/admin1/seguroscrm` (checkout git de `main`, remoto `origin` = GitHub) |
| Contenedores | `seguroscrm_app` (php-fpm), `seguroscrm_webserver` (nginx, puerto **8085**), `seguroscrm_db` (MySQL 8, alias `mysql`) |
| CRM | http://192.168.1.51:8085/admin |
| Repositorio | https://github.com/jrodriduran/seguroscrm (privado) |
| Cron del host (crontab de `admin1`) | `* * * * * docker exec seguroscrm_app php artisan schedule:run` |

El código se monta en el contenedor (`./:/var/www`). Los cambios en el
servidor se ven al instante tras limpiar cachés.

Comandos habituales (en el servidor):

```bash
cd /home/admin1/seguroscrm
docker exec seguroscrm_app php -l <archivo>
docker exec seguroscrm_app php artisan migrate --force
docker exec seguroscrm_app php artisan optimize:clear && docker exec seguroscrm_app php artisan view:cache
docker exec seguroscrm_app composer dump-autoload      # tras añadir un paquete nuevo
```

**Secretos:** están en `.env` del servidor (no versionado) y en la tabla
`communication_settings` (token de Chatwoot, cifrado). Nunca los copies al
repo, a commits ni a la conversación.

## 3. Flujo de git (obligatorio)

- **`main` = producción siempre.** El servidor corre `main`.
- Trabaja en una rama `feature/<tema>` → PR contra `main` → merge con
  **merge commit** (asunto `Merge: <descripción> (#N)`) → en el servidor
  `git checkout main && git pull`, luego migrate y limpiar cachés.
- Autor de los commits: `jrodriduran <jrodriduran@gmail.com>`.
- **CI** (GitHub Actions):
  - *Linting Tests* corre Pint y **hace un auto-commit** (`chore: applied pint changes`) en la rama. Haz pull antes de seguir trabajando en ella.
  - *CI* (Pest): la ejecución paralela histórica de `main` reportaba **91 failed / 54 passed**. La reproducción aislada en serie del commit `bc61f60` da **1 failed / 144 passed**; el fallo es una prueba del webhook antiguo de Chatwoot. Al actualizar esa prueba y añadir casos de autenticación y cuenta ajena, la suite en serie da **147 passed / 858 assertions**. Los trabajadores paralelos migran bases separadas sin los datos del instalador que requieren muchas pruebas. CI ahora ejecuta la suite completa en serie sobre su base sembrada. **No aceptar una cuota de fallos heredados:** cualquier fallo debe investigarse. El resultado aislado no implica que se hayan validado integraciones externas ni Playwright.
  - *Playwright* (6 shards): también falla en `main`. Hoy no bloquea.
- Cada cambio actualiza `CHANGELOG.md` bajo `v2.2.7 (unreleased)`.
- Después de un merge, borra la rama remota.

## 4. Trampas conocidas (te van a pasar)

1. **ACL fail-closed.** El Bouncer de Krayin **deniega cualquier ruta admin
   que no esté en `config('acl')`**. La autoriza el prefijo mapeado más
   cercano. Cada ruta nueva necesita su entrada en `src/Config/acl.php` del
   paquete. Si usuarios no admin deben verla, añade una migración que conceda
   el permiso a sus roles (ejemplo: `2026_10_03_100002` de Communications).
2. **Vue monta `#app`** sobre todo el layout admin:
   - Si una vista renderizada en servidor muestra texto del usuario, envuélvela en `v-pre`, o Vue interpretará `{{ }}`.
   - Para JS, usa delegación a nivel de documento dentro de `@pushOnce('scripts')`.
   - Los estilos van en un partial del `<head>` (por ejemplo `communications::partials.head`).
3. **Blade:** no mezcles `@php(...)` en línea con un bloque `@php … @endphp`
   en la misma vista, porque rompe la compilación (error 500). Usa siempre el bloque.
4. **Tailwind precompilado.** El CSS admin está construido en
   `public/admin/build`. Si usas una clase de Tailwind que no existe en el
   build, no se aplica. Puedes usar CSS propio con prefijo (`cm-*`, `tw-*`,
   `nv-*`) en los partials de head, o reconstruir solo el CSS con el CLI de
   Tailwind 3.4 en un contenedor `node:20-alpine` desechable (sin tocar el
   bundle JS) y actualizar `manifest.json`.
5. **MySQL:**
   - Los nombres de índice tienen máximo 64 caracteres; nombra explícitamente los índices compuestos largos.
   - El DDL no es transaccional: si una migración falla a medias, deja tablas parciales que hay que borrar a mano.
   - Las columnas JSON de Krayin (por ejemplo, la dirección de personas) se guardan con espacios. Usa `json_unquote(json_extract(...))`, nunca `LIKE`.
6. **Columnas ambiguas en joins:** califica siempre `tabla.columna` (hubo
   errores con `status` y `created_at`).
7. **Zona horaria:** la de la agencia sale de
   `core()->getConfigData('general.general.timezone.timezone')` y se aplica
   con `Webkul\Teamwork\Http\Middleware\ApplyTimezone::resolve()`, incluido en
   comandos de consola. El `APP_TIMEZONE` del `.env` actual (Asia/Kolkata) no
   se usa para la lógica.
8. **Páginas de error:** el handler admin ya devuelve el código HTTP real
   (antes devolvía 200). En los tests, espera 403, 404, etc.
9. **`InsuranceSla`:** al llamar a `leadRepository->update()` fuera del
   formulario de lead, pasa `collect()` como colección de atributos, o se
   resetean los booleanos.
10. **Windows → servidor:** si editas archivos desde Windows, cuida los finales
    de línea. Los scripts bash con CRLF fallan (`set: pipefail: invalid option`).

## 5. Paquetes propios (`packages/Webkul/…/src`)

Además de los módulos de Krayin y de las funciones de seguros que ya traía el
repo (pólizas, comisiones, DMI, SOA de Medicare, cumplimiento, etc.), estos
paquetes se construyeron en este proyecto:

### Insurance
Extensiones propias del dominio, separadas del core. `InsuranceServiceProvider`
vincula el controlador de cotizaciones existente con una subclase que modifica
solo `convertToPolicy`. Conserva rutas, middleware y ACL. Usa una transacción y
bloqueo de cotización, la etapa `won` del embudo correspondiente y los bloqueos
del playbook; los reintentos no duplican comisiones ni reinician coberturas pagadas.
Los modelos heredados permanecen en Lead. No añade inscripción externa.

### Security
2FA, lista de IP permitidas (`security:disable-ip-restriction` para
emergencias), historial de accesos (`user_access_logs`, modelo `AccessLog`
con eventos que incluyen `support_login` y `password_reset`) y el middleware
`EnforceRoutePermissions`.

### Teamwork (seguimiento y trabajo en equipo)
- **Centro de seguimiento**, follow-ups con prioridad y urgencia.
- **Notificaciones** (campana).
- **Notas entre compañeros** con acuse de lectura, @menciones y seguir registros.
- **Automatizaciones de equipo:** `teamwork:run-automations`, cada hora.
- **Playbook del pipeline** (Configuración → Seguimiento → Playbook):
  - Por etapa: hitos (`teamwork_stage_milestones`, 18 chequeos en `Services/MilestoneChecks`), en modo *warn* o *block*.
  - Tareas automáticas al entrar a una etapa y tareas de arrastre de lo pendiente (`teamwork_follow_ups.source = playbook:{lead}:{stage}:entry|carry`).
  - Excepciones del master con motivo (`teamwork_stage_overrides`).
  - El middleware `EnforceStageGate` devuelve 422 si el lead no cumple los hitos de una etapa en modo *block*.
  - Muestra un badge en la tarjeta del kanban (`LeadResource['playbook']`) y un panel en la vista del lead.
- **Marca:** `Support/Brand.php` y la sección de configuración `general.general.brand` (nombre, email, teléfono y URL de soporte). Alimenta `app.name`, la página de Ayuda y los pies de página. Ya no queda ningún enlace a Krayin.

### Communications (comunicaciones con el cliente)
- Consentimiento por canal (`ContactConsent`, TCPA) y resultados de llamada.
- Panel de comunicaciones del contacto (línea de tiempo unificada).
- Autocompletado de dirección por ZIP.
- **Chatwoot:**
  - Hay que crear una cuenta por agencia. SegurosCRM = cuenta **4**, inbox API de prueba **3**, en chatwoot.inteprix.com.
  - ⚠️ **La cuenta 3 "Inteprix" es de otro negocio: no tocarla nunca.**
  - El webhook entrante no llega todavía, porque Chatwoot bloquea IPs privadas. Se resolverá con el dominio público.
- **Plantillas** por canal e idioma con variables (`TemplateRenderer`) y **secuencias** con disparadores:
  - Por evento, por etapa, por fecha (cumpleaños, renovación) y por días sin movimiento en una etapa.
  - Los pasos son email, SMS/WhatsApp vía Chatwoot, tarea o espera.
  - Motor: `SequenceEngine`, `StepActions`, `MessageDispatcher`.
  - El CRM envía los emails él mismo (no usa Mautic) y registra aperturas y clics.
  - Hay 6 secuencias y 13 plantillas de inicio, **pausadas**.
- **Comunicaciones por etapa** (Configuración → Comunicaciones → Etapas).
- **Listas (audiencias) y campañas:**
  - Filtros por estado, ciudad, sexo, edad, tipo de póliza, etc. (`AudienceQuery`).
  - `CampaignRunner` envía en lotes de 60 dentro del horario permitido y respeta el consentimiento.
  - `Occasions` calcula las fechas especiales de EE. UU. (Día de las Madres, etc.).
- **Envíos físicos** (tarjetas, gift cards): tablero `admin/communications/deliveries` con flujo solicitado → aprobado → enviado y gasto mensual por agente.
- **Desuscripción** pública con enlace firmado.
- Comandos:
  - `communications:run-sequences`, cada 5 minutos. También ejecuta las campañas.
  - `communications:backfill-addresses`.

### Platform (lado instancia del SaaS)
- `platform_state` guarda el estado de la cuenta: `active`, `warning` (banner), `read_only` o `suspended`.
  - En `read_only`, todo lo que no sea GET responde 423 o vuelve atrás con un mensaje flash.
  - En `suspended` se muestra una pantalla de "contacta a soporte" con código 402.
  - El login, la recuperación de contraseña y el 2FA siempre quedan permitidos.
- Comandos:
  - `php artisan platform status [estado] --message=`
  - `support-link [email] --reason=`: enlace de un solo uso, válido 60 s, con token hasheado en `platform_support_tokens`.
  - `reset-owner [email] --disable-2fa`
  - `setup-owner email --name= --timezone=`: además marca la instalación como completa (ver §7).
- API firmada para el futuro panel del operador: `/platform/api/{health,status,support-link,reset-owner}` con HMAC SHA-256 sobre `"{ts}.{METHOD}.{path}.{body}"`.
  - Cabeceras: `X-Platform-Timestamp` y `X-Platform-Signature`.
  - Usa el secreto `PLATFORM_SECRET`. Si no está definido, la API responde 404.

### Programador (`routes/console.php`)
- `communications:run-sequences`: cada 5 minutos.
- `teamwork:run-automations`: cada hora.
- `campaign:process`: a diario (Krayin).
- Comandos `insurance:*` (SLA, DMI, períodos de gracia, cumplimiento).
- `inbound-emails:process`: solo si el receptor es `webklex-imap`.

## 6. Pruebas

- Pest en `tests/Feature/…`. Ver la línea base en §3.
- Durante el desarrollo se usaron scripts de humo que corren dentro del
  contenedor contra la base real: rutas, permisos de rol asistente,
  renderizado y paridad de traducciones. Para cualquier pantalla nueva,
  comprueba como mínimo:
  - que responde 200 para el admin y para un usuario no admin con el permiso;
  - que devuelve 403 sin el permiso;
  - que no aparece texto de traducción sin resolver (`::app.`);
  - que tiene paridad de claves en es, en y pt_BR.
- `/home/admin1/platform_test.sh` en el servidor: pruebas de punta a punta de Platform (18/18).
- **No corras Pest contra la base del servidor.** Los tests usan
  `RefreshDatabase` y la borrarían. Corre Pest en CI o con una base de pruebas aparte.

### Respaldos de la instalación LAN

- Runner: `deploy/lan/backup.py`; copia operativa en `/home/admin1/seguroscrm-ops/backup.py`.
- Cron de `admin1`: copia diaria a las 03:00, hora del servidor (America/New_York), y comprobación cada hora al minuto 15. Conserva intacto el cron de Laravel.
- Destino privado: `/home/admin1/seguroscrm-backups`; SQL, `storage/app`, `.env` y checksums. Retención de 14 días solo para copias creadas por el runner.
- `python3 /home/admin1/seguroscrm-ops/backup.py --check` detecta último intento fallido, copia alterada o antigüedad superior a 26 horas. Estado en `last-run.json`, `latest.json` y `/home/admin1/seguroscrm-ops/health.log`.
- Primera copia del runner restaurada en MySQL aislado: 120 tablas y 143 migraciones. Los contenedores de verificación quedaron detenidos.
- Detección local, sin notificaciones externas ni copia fuera del host todavía. Ver `deploy/lan/README.md` y sus pruebas de fallos; nunca publicar estos respaldos como artifacts.

## 7. SaaS (`deploy/saas/`, ver su README)

- **Arquitectura:**
  - Un VPS con Traefik (HTTPS automático), MySQL y Redis compartidos.
  - Por cliente, un proyecto de compose `crm-<cliente>` con los contenedores web, worker y scheduler de una sola imagen versionada.
  - Cada cliente tiene su propia base de datos, su prefijo de Redis y su `.env` en `/opt/saas/tenants/<cliente>/`.
  - Chatwoot va en otro VPS, con una cuenta por agencia.
- **Scripts** (en `deploy/saas/bin/`):
  - `new-client`
  - `update`: canario y luego oleadas, con respaldo previo y rollback automático.
  - `backup`, `restore`, `list`, `support`
  - Todos se probaron de punta a punta en un entorno aislado.
- **Trampa:** en los contenedores no hay archivo `.env`. Krayin considera la
  app "no instalada" y redirige a `/install` salvo que existan
  `storage/installed` y `core_config.installation.completed`. `setup-owner`
  crea las dos marcas.
- `TestEnvironmentSeeder`, que crea usuarios demo con contraseña conocida,
  **no corre en producción**. No quites esa guarda.
- Detrás del proxy hace falta `TRUSTED_PROXIES=*` (lo pone `new-client`).

## 8. Pendiente

### Se puede hacer ya (sin dominio)
1. **Panel del operador SaaS:** una app aparte, fuera de las instancias de los clientes. La propuesta es Laravel + Filament. Debe permitir:
   - dar de alta clientes;
   - ver los que corren, con su salud, versión y estado;
   - entrar al panel de cada cliente con el enlace de soporte;
   - resetear la contraseña del master;
   - cambiar el estado de la cuenta (aviso, solo lectura, suspendido).
   
   Debe consumir la API firmada de Platform. Para las altas y actualizaciones, debe hablar con un agente en el host que ejecute los scripts de `deploy/saas/bin`; la app web no debe montar `docker.sock`. Los webhooks de Stripe vendrán después.
2. **Mantener Pest sin fallos y mejorar el aislamiento de fixtures.** La suite en serie pasa; para recuperar el paralelismo, preparar datos independientes por prueba o trabajador. Playwright sigue pendiente de diagnóstico.
3. **Puntaje de salud del cliente** (customer care): actividad, pólizas, pagos, respuestas y consentimiento.
4. **Unificar las automatizaciones de Teamwork con las secuencias de Communications** en un solo motor.
5. Ajustar dos expectativas viejas del test de secuencias. Ahora hay 13 plantillas de inicio, y el texto que buscaba el test en el índice de plantillas cambió.
6. **Extraer un núcleo compartido** (Security, Teamwork, Communications, Platform, tema) para el próximo CRM genérico, también sobre Krayin. Mantén el código genérico fuera de los paquetes de seguros.

### Depende del dueño
- Nombre del producto y dominio con HTTPS. Con eso se monta el VPS y se activa el webhook de Chatwoot.
- Cuenta de correo saliente. El driver de Postmark está instalado; el hostname
  mailhog configurado no existe en la red actual y los mensajes no se entregan.
- Número de WhatsApp de Meta (WABA).
- Activar las secuencias de inicio, llenar los datos de la agencia y la marca, y pasar a *block* las etapas críticas (consentimiento ACA, SOA de Medicare).
- Revisión visual del modo oscuro.
- Decidir si se borra el contacto de prueba "Prueba Chatwoot (borrar)".

## 9. Reglas del dueño

- Este proyecto es independiente. No lo mezcles con otros proyectos locales del dueño.
- Pide confirmación antes de cualquier cosa irreversible o visible desde fuera (borrar datos, tocar Chatwoot u otras cuentas, enviar mensajes reales).
- El dueño escribe en español. Responde en español.
