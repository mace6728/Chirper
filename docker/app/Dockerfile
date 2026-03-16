FROM php:8.4-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    unzip \
    git \
    bash

RUN docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    pdo \
    pdo_pgsql \
    zip

RUN addgroup -S -g ${WWWGROUP} appgroup \
    && adduser -S -D -u ${WWWUSER} -G appgroup -h /home/app app \
    && mkdir -p /home/app \
    && chown -R ${WWWUSER}:${WWWGROUP} /home/app

ENV HOME=/home/app

WORKDIR /var/www/html

EXPOSE 9000
