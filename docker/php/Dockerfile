
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --no-cache \
      git curl wget bash shadow icu-libs icu-dev \
      libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
      oniguruma-dev autoconf g++ make \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring intl gd zip bcmath opcache \
  && apk del g++ make autoconf

WORKDIR /var/www/html
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# (No COPY healthz.php)
