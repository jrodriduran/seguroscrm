# Imagen de producción del CRM: una sola imagen para todos los clientes.
# El rol del contenedor se elige con CONTAINER_ROLE = web | worker | scheduler.
#
#   docker build -f deploy/saas/Dockerfile --build-arg APP_VERSION=1.4.0 -t seguroscrm:1.4.0 .

# --- Dependencias PHP (sin paquetes de desarrollo) ---
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY packages ./packages

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-scripts

# --- Imagen final ---
FROM php:8.3-fpm-bookworm

ARG APP_VERSION=dev

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor curl unzip default-mysql-client \
        libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql mbstring exif pcntl bcmath intl zip calendar opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev \
    && apt-get install -y --no-install-recommends libpng16-16 libonig5 libxml2 libzip4 libicu72 libfreetype6 libjpeg62-turbo \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY deploy/saas/docker/php.ini /usr/local/etc/php/conf.d/zz-crm.ini
COPY deploy/saas/docker/nginx.conf /etc/nginx/sites-available/default
COPY deploy/saas/docker/supervisord.conf /etc/supervisor/conf.d/crm.conf
COPY deploy/saas/docker/entrypoint.sh /usr/local/bin/crm-entrypoint

WORKDIR /var/www

COPY --from=vendor --chown=www-data:www-data /app /var/www

RUN chmod +x /usr/local/bin/crm-entrypoint \
    && echo "${APP_VERSION}" > /var/www/VERSION \
    && rm -rf /var/www/deploy /var/www/tests /var/www/.git* \
    && mkdir -p /var/www/storage/app/public /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

ENV CONTAINER_ROLE=web \
    XDG_CONFIG_HOME=/tmp/.config

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD [ "$CONTAINER_ROLE" != "web" ] || curl -fsS http://127.0.0.1/up -o /dev/null || exit 1

ENTRYPOINT ["crm-entrypoint"]
