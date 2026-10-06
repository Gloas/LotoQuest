# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader


FROM php:8.5-apache

# Slim needs mod_rewrite and the .htaccess files, served from public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    docker=true \
    LOTOQUEST_DRAWS_DIR=/var/www/html/csv
RUN a2enmod rewrite \
 && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY app ./app
COPY src ./src
COPY public ./public
COPY composer.json ./

RUN mkdir -p csv/adulte csv/enfant logs var/cache \
 && chown -R www-data:www-data csv logs var

# drawn numbers survive container restarts
VOLUME /var/www/html/csv

EXPOSE 80
