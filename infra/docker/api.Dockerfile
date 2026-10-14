FROM php:8.4-fpm-bookworm AS base
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev git unzip \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app/apps/api
ENV COMPOSER_ALLOW_SUPERUSER=1
FROM base AS development
RUN pecl install pcov xdebug && docker-php-ext-enable pcov xdebug
FROM base AS production
COPY apps/api/composer.json apps/api/composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --classmap-authoritative
COPY apps/api/ ./
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts
RUN mkdir -p var/cache var/log /var/lib/furniture/files && chown -R www-data:www-data var /var/lib/furniture/files
USER www-data
