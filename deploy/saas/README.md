# SaaS: un CRM por cliente en un VPS

Cada agencia tiene **su propia instancia** (sus contenedores, su base de datos,
sus archivos, su dominio) y todas comparten un proxy con HTTPS, un MySQL y un
Redis. Una sola imagen sirve a todos; cada cliente puede estar en una versión
distinta mientras se actualiza.

```
              Internet
                 │ 443
            ┌────┴────┐
            │ Traefik │  certificados automáticos (Let's Encrypt)
            └────┬────┘
     ┌───────────┼───────────┐
 crm-acme     crm-beta     crm-...      web + worker + scheduler por cliente
     └───────────┼───────────┘
          MySQL (una base por cliente) · Redis (prefijo por cliente)
```

## Primera vez en el VPS

```bash
sudo mkdir -p /opt/saas && sudo chown $USER /opt/saas
cp deploy/saas/saas.env.example /opt/saas/saas.env && chmod 600 /opt/saas/saas.env
nano /opt/saas/saas.env                      # dominio, correo ACME, claves
docker compose -p saas -f deploy/saas/compose.shared.yml --env-file /opt/saas/saas.env up -d
```

El DNS necesita un comodín: `*.crm.ejemplo.com → IP del VPS`.

## Construir una versión

```bash
docker build -f deploy/saas/Dockerfile --build-arg APP_VERSION=1.0.0 -t seguroscrm:1.0.0 .
```

Con un registry (recomendado cuando haya más de un VPS): `IMAGE=registry.ejemplo.com/seguroscrm`
en `saas.env` y `docker push` de cada versión.

## Operación diaria

| Tarea | Comando |
|---|---|
| Alta de cliente | `bin/new-client acme dueño@acme.com --name "Ana Pérez" --tz America/New_York` |
| Dominio propio del cliente | añadir `--domain crm.acme.com` (su DNS apunta al VPS) |
| Ver clientes | `bin/list` |
| Salud de uno | `bin/support acme health` |
| Entrar a su panel (soporte) | `bin/support acme link --reason "ticket 123"` → enlace de un solo uso, 1 minuto |
| Dueño perdió la contraseña | `bin/support acme reset-owner` (añade `--disable-2fa` si perdió el teléfono) |
| Aviso de pago | `bin/support acme warning "Tu pago de octubre está pendiente"` |
| Solo lectura | `bin/support acme read_only` (ven y exportan; no crean ni cambian nada) |
| Suspender | `bin/support acme suspended` (pantalla de "contacta a soporte"; no se borra nada) |
| Reactivar | `bin/support acme active` |
| Respaldo | `bin/backup acme` · todos: `bin/backup --all` |
| Restaurar | `bin/restore acme /opt/saas/backups/acme/acme-20261003-020000.sql.gz` |
| Logs | `bin/support acme logs` |

Las entradas de soporte y los cambios de contraseña quedan en el **historial de
accesos de la agencia** (Configuración → Seguridad), para que el cliente vea
cuándo entró el soporte.

## Actualizar a una versión nueva

```bash
bin/update 1.1.0 --only acme     # canario: un cliente primero, se revisa
bin/update 1.1.0                 # el resto, de 5 en 5 con pausa entre oleadas
```

Por cliente: respaldo → contenedores nuevos → migraciones → prueba. Si falla,
ese cliente vuelve solo a su versión anterior y a su respaldo, y la
actualización se detiene. Las migraciones deben ser **aditivas** (agregar
columnas/tablas; borrar solo en una versión posterior) para que una vuelta
atrás nunca pierda datos.

## Cron del VPS

```cron
0 2 * * * /ruta/al/repo/deploy/saas/bin/backup --all >> /opt/saas/backup.log 2>&1
```

Los respaldos quedan en `BACKUP_DIR`; conviene copiarlos fuera del VPS
(por ejemplo `rclone` a un bucket S3/B2) en el mismo cron.

## API para el futuro panel del SaaS

Cada instancia expone `/platform/api/*` firmado con su `PLATFORM_SECRET`
(está en `/opt/saas/tenants/<cliente>/.env`):

| Método | Ruta | Cuerpo |
|---|---|---|
| GET | `/platform/api/health` | — |
| POST | `/platform/api/status` | `{"status":"read_only","message":"..."}` |
| POST | `/platform/api/support-link` | `{"email":null,"reason":"..."}` |
| POST | `/platform/api/reset-owner` | `{"email":null,"disable_two_factor":false}` |

Cabeceras: `X-Platform-Timestamp` (segundos unix) y `X-Platform-Signature` =
`hex(hmac_sha256("{timestamp}.{MÉTODO}.{ruta}.{cuerpo}", PLATFORM_SECRET))`,
con la ruta sin barra inicial (`platform/api/health`). Firmas de más de 5
minutos se rechazan. Sin `PLATFORM_SECRET` la API responde 404.

## Archivos

```
deploy/saas/
├── Dockerfile               imagen de producción (nginx + php-fpm, rol por CONTAINER_ROLE)
├── compose.shared.yml       Traefik, MySQL, Redis
├── compose.tenant.yml       los 3 contenedores de un cliente
├── saas.env.example         configuración del VPS
├── docker/                  nginx, php.ini, supervisor, entrypoint
└── bin/                     new-client, update, backup, restore, list, support
/opt/saas/
├── saas.env
├── tenants/<cliente>/.env   claves del cliente (600)
├── tenants/<cliente>/VERSION
└── backups/<cliente>/
```
