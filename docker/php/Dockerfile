FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    zip \
    unzip \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html