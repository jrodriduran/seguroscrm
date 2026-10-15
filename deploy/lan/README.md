# Copias de la instalación LAN

Este runner es para `/home/admin1/seguroscrm` y su contenedor `seguroscrm_db`.
No sustituye los scripts del futuro SaaS ni configura servicios de otros proyectos.
Requiere Linux, Python 3.11+ y acceso a Docker como el usuario de la instalación.

```bash
python3 deploy/lan/backup.py
python3 deploy/lan/backup.py --check
python3 -m unittest discover -s deploy/lan -p 'test_*.py' -v
```

Guarda SQL, `storage/app` y `.env` fuera del repositorio en
`/home/admin1/seguroscrm-backups`, con directorios 700 y archivos 600. El `.env`
conserva la clave necesaria para descifrar configuración. No publicar ni descargar
estos respaldos como artefactos de CI. Las credenciales de MySQL permanecen en
el contenedor. Los archivos contienen información privada y no están cifrados.

Solo publica la carpeta del respaldo después de completar y verificar sus archivos.
La última copia correcta se conserva si el volcado falla. `latest.json` identifica
la copia correcta; `last-run.json` registra si el último intento falló. La retención
de 14 días elimina únicamente carpetas reconocidas creadas por este runner.
El respaldo manual inicial no se elimina. `--check` falla si el último intento falló,
faltan archivos, cambian los checksums o la copia tiene más de 26 horas.

Para que la automatización funcione antes de desplegar la app, instalar una copia
versionada de `backup.py` en `/home/admin1/seguroscrm-ops/backup.py`. Cron del usuario
`admin1` (hora del servidor, actualmente America/New_York):

```cron
0 3 * * * /usr/bin/python3 /home/admin1/seguroscrm-ops/backup.py > /home/admin1/seguroscrm-ops/backup.log 2>&1
15 * * * * /usr/bin/python3 /home/admin1/seguroscrm-ops/backup.py --check > /home/admin1/seguroscrm-ops/health.log 2>&1
```

Los logs contienen el resultado y nombres de archivos, sin contenido de la base
ni secretos. `backup.log` se reemplaza cada día y `health.log`
se reemplaza cada hora. La detección es local: falta un destino externo de alertas
y una copia fuera del servidor para proteger contra la pérdida del host.

## Comprobar recuperación

Restaurar el SQL exclusivamente en un contenedor MySQL de pruebas sin puertos
publicados, con credenciales propias, y comparar tablas y migraciones con la copia.
Verificar en el archivo tar que estén `.env` y los documentos esperados. No ejecutar
un restore sobre la instalación activa para probar una copia. Los archivos y la base
se capturan consecutivamente, sin detener la app; para operaciones que requieran
consistencia exacta entre documentos y SQL, coordinar una ventana de mantenimiento.
