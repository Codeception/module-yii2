ARG PHP_VERSION="8.3-alpine"

FROM php:$PHP_VERSION

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions \
    && apk add --no-cache git \
    && install-php-extensions gd intl zip intl pcov @composer

WORKDIR /var/www/html