#!/bin/sh
# Arranque de un contenedor del CRM según su rol.
set -e

cd /var/www

# El volumen de storage llega vacío la primera vez.
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

[ -L public/storage ] || ln -sfn ../storage/app/public public/storage

run() { su -s /bin/sh www-data -c "php artisan $*"; }

case "${CONTAINER_ROLE:-web}" in
    web)
        run optimize:clear >/dev/null
        run view:cache >/dev/null
        exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
        ;;
    worker)
        exec su -s /bin/sh www-data -c "php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
        ;;
    scheduler)
        exec su -s /bin/sh www-data -c "php artisan schedule:work"
        ;;
    *)
        exec "$@"
        ;;
esac
