FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip zip \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip \
    && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
