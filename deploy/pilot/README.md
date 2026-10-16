# Piloto de agencia: preparación y aceptación

Este documento distingue código preparado de operación real. No confirma un
despliegue ni la aprobación de proveedores. La prioridad es una dueña y dos agentes,
ACA y Medicare. La conexión de WhatsApp y la marca quedaron aplazadas por el dueño.

## Cambios preparados

- Conversión de cotización a póliza con transacción, control de etapas y reintentos
  sin duplicar comisiones ni reiniciar la cobertura pagada.
- Grupo familiar, transición de cobertura, tarea con responsable y expediente
  individual vinculado. Cumplir 26 años genera revisión, nunca una baja automática.
- Consulta interna de cumpleaños con teléfono enmascarado y consentimiento por
  canal. No equivale a mensajería de WhatsApp operativa.
- Rutas corregidas de medicamentos, médicos y venta cruzada; control del alcance
  del agente en esos recursos y en documentos de consentimiento.
- Documentos nuevos con identidad configurada por agente. No se usa un NPN o
  nombre de agencia de ejemplo. El nombre comercial de la plataforma es separado.
- Historial firmado ACA conservado al renovar; consentimiento revocado no se puede
  volver a firmar con el enlace antiguo. SOA firmado no puede sobrescribirse ni
  reiniciarse: una futura modificación necesita historial de versiones de SOA.
- HealthSherpa desactivado. La interfaz no ofrece una inscripción que no existe.
- Revisión previa de CSV sin crear, actualizar ni fusionar personas.
- Flujo de asignación, revisión de vencimientos, cierre y supervisión probado con
  una dueña y dos agentes sintéticos. La reapertura de una tarea terminada exige
  ser responsable, solicitante o dueña. Los usuarios sin agencia no ven otros equipos.
- Todas las claves de traducción de Admin disponibles en los diez idiomas, con
  textos de respaldo en inglés donde faltaba traducción. Formulario de correo
  comprobado visualmente dentro de la pantalla con CC/BCC, sin enviar mensajes.

## Configurar los usuarios y su identidad

Crear los tres usuarios con sus correos reales desde Configuración. La dueña
decide quién ve todos los expedientes y si los agentes ven los propios o el grupo.
Conceder únicamente los permisos necesarios de leads, seguimiento, grupo familiar,
creación de expedientes individuales y cumpleaños. No copiar el rol de administrador
a los agentes por comodidad. Probar lectura, edición y denegación con cada usuario.

Después de revisar los datos con la agencia, un operador configura cada agente:

```bash
php artisan insurance:agent-identity 'agente@example.invalid' '900000001' 'AGENCIA SINTÉTICA: SUSTITUIR'
```

El ejemplo es sintético, no debe ejecutarse contra usuarios reales. Este comando
requiere un usuario existente y activo. Guarda NPN y nombre legal para documentos
futuros; **no consulta NIPR, no verifica licencias ni autoriza nombramientos**.
Si falta identidad, generar o renovar un documento devuelve un error de validación.
Los documentos firmados conservan los datos históricos. Revisar y regenerar los
documentos pendientes heredados que contienen la agencia ficticia anterior; sus
portales de firma quedan bloqueados. No corregir firmas directamente en SQL.

## Preparar la carga inicial

CSV UTF-8, separado por coma y con esta cabecera exacta:

```csv
name,email,phone,date_of_birth,owner_email
```

Fechas `YYYY-MM-DD`; teléfono de EE. UU./NANP con 10 dígitos o prefijo `+1`.
Al menos un email o teléfono válido y responsable activo. DOB vacía significa
desconocida; no se inventa y ese cliente no podrá recibir la automatización por
cumpleaños hasta completarla. No incluir SSN, Medicare ID, ingresos ni documentos.
El comando rechaza cabeceras adicionales y archivos superiores a 10 MB/50.000 filas.

```bash
php artisan insurance:preview-contacts /ruta/privada/contactos.csv
```

Salida: número de fila, estado y códigos de revisión, sin nombres ni teléfonos.
`ready` indica que la fila pasa esta revisión, **no que fue importada**. Email o
teléfono coincidente se marca para revisión, incluyendo coincidencias dentro del
archivo. Un teléfono familiar compartido no autoriza una fusión. Salida distinta
de cero indica filas inválidas o que requieren revisión. La carga real usa el
importador existente, con copia previa, muestra pequeña y mapeo revisado; este
comando no reemplaza ni bloquea ese importador automáticamente.

## Rutina diaria de aceptación con la dueña

1. Revisar Seguimiento: vencidos, tareas de hoy y expedientes sin próxima acción.
2. Asignar responsable y fecha a cada pendiente; comprobar lo que ve cada agente.
3. Revisar cumpleaños: DOB, responsable, consentimiento y teléfono. No enviar
   automáticamente hasta disponer de conexión oficial y plantilla aprobada.
4. En el expediente, registrar documentos pendientes, cambios familiares y próxima
   acción. Una transición confirmada necesita una referencia verificable.
5. Al cerrar el día, comprobar tareas terminadas, atrasadas y reasignadas. Probar
   con la dueña un caso ACA y otro Medicare, incluida renovación y comisión.

Pruebas automatizadas con datos sintéticos no sustituyen esta aceptación comercial.
No se han creado usuarios reales ni importado la cartera de la clienta.

## Antes de desplegar

- Respaldo reciente y saludable; revisar `deploy/lan/README.md` y conservar el
  procedimiento de recuperación. El respaldo local no constituye copia externa.
- Migraciones y pruebas en entorno aislado. **Nunca Pest en producción.**
- Revisar los cambios y fusionar con merge commit conforme a HANDOFF.md; actualizar
  el servidor desde main, ejecutar migraciones, limpiar cachés y compilar vistas.
- Prueba de acceso de los tres usuarios y de las pantallas críticas tras desplegar.
- Configurar HTTPS, correo real y conexión oficial de WhatsApp antes de entregarlo
  como servicio operativo. El hostname mailhog actual no entrega correo.
- Probar recuperación en un destino aislado; no restaurar encima de la producción
  para comprobar un respaldo. La existencia del respaldo no prueba su restauración.
- HealthSherpa requiere cuenta y accesos concedidos por el proveedor; revisar
  `docs/healthsherpa-integration.md` antes de activar el conector.

## Alcance que sigue abierto

- Número y API oficial de WhatsApp, plantillas y ventanas de atención, deduplicación
  de envíos, vencimiento de mensajes y confirmación de entrega.
- Datos reales, permisos finales de la agencia, carga de cartera y aceptación de
  la dueña. Dominio/HTTPS, correo y copia externa de respaldos.
- Verificación completa de navegador: diagnosticar cada fallo de Playwright; no
  suprimir pruebas ni aceptar un porcentaje de fallos como criterio de entrega.
- Identidad permanente de integrantes a través de distintos hogares/años y enlace
  con clientes existentes antes de crear otro contacto; el vínculo actual evita
  duplicar el expediente de una misma transición, no deduplica toda la cartera.
- Versionado de modificaciones SOA; el bloqueo actual protege la firma existente.
- Inventario de licencias completo y revisión de distribución. `LICENSE` de Krayin
  se conserva. `THIRD_PARTY_DEPENDENCIES.json` reproduce solo las licencias
  declaradas por Composer, incluye dependencias con GPL/LGPL y no constituye una
  conclusión legal. Faltan inventario de JS/activos y textos de avisos aplicables.
- Panel del operador SaaS, motor unificado de automatizaciones y puntaje de salud
  del cliente pertenecen a fases posteriores, fuera de la aceptación de este piloto.
