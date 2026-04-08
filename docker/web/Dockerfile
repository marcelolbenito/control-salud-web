FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
