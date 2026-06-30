FROM php:8.5-fpm-alpine

# Build arguments for Xdebug configuration
ARG XDEBUG_IDEKEY=phpstorm
ARG XDEBUG_CLIENT_HOST=host.docker.internal
ARG XDEBUG_CLIENT_PORT=9003

# Set as environment variables for envsubst
ENV XDEBUG_IDEKEY=${XDEBUG_IDEKEY}
ENV XDEBUG_CLIENT_HOST=${XDEBUG_CLIENT_HOST}
ENV XDEBUG_CLIENT_PORT=${XDEBUG_CLIENT_PORT}

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    gettext \
    && docker-php-ext-install zip pdo pdo_mysql

# Install Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Copy and process Xdebug configuration
COPY docker/php/xdebug.ini /tmp/xdebug.ini.template
RUN envsubst < /tmp/xdebug.ini.template > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && rm /tmp/xdebug.ini.template

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Grant read/write/execute permissions to all users on the document root folder
RUN chown -R www-data:www-data /var/www/html

