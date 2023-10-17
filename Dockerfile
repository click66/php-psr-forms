FROM php:8.2

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt-get update -y && apt-get install -y git gnupg unzip zip zlib1g-dev libzip-dev
RUN docker-php-ext-install zip

WORKDIR /app

COPY composer.* .
RUN composer install

COPY src src
COPY tests tests
