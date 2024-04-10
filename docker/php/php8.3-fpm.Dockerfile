FROM php:8.3-fpm

RUN apt-get update && docker-php-ext-install mysqli

COPY ./php.ini /usr/local/etc/php/php.ini

WORKDIR /src