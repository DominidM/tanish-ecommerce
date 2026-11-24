# ---------------------------------------------------------------------------
# Imagen de producción del servicio Laravel (TANISH Admin / Integración)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-req=ext-pcntl \
    --ignore-platform-req=ext-intl

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    --ignore-platform-req=ext-pcntl \
    --ignore-platform-req=ext-intl


FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip curl \
    && docker-php-ext-install intl pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN php artisan package:discover --ansi \
    && php artisan filament:assets \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
