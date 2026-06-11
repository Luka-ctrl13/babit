FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    nginx \
    supervisor \
    postgresql-dev \
    libpq \
    curl \
    unzip \
    git \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    linux-headers \
    gettext \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
 && mkdir -p /var/log/supervisor /run/nginx /run/php

# Install PHP extensions (pcntl + posix required by Laravel Horizon)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    mbstring \
    xml \
    bcmath \
    intl \
    opcache \
    zip \
    gd \
    fileinfo \
    exif \
    pcntl \
    posix \
    sockets

# Install Redis extension via PECL
RUN pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer cache)
COPY composer.json composer.lock ./

# Unlimited memory during composer install
RUN echo "memory_limit=-1" > $PHP_INI_DIR/conf.d/memory.ini

RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs

# Copy full application
COPY . .

# Set storage permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Dump optimized autoload (with full app present)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-scripts

# Copy runtime configs
COPY docker/nginx.conf       /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini          $PHP_INI_DIR/conf.d/app.ini
COPY docker/entrypoint.sh    /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
