FROM php:8.3-fpm-alpine

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
    gettext

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    xml \
    bcmath \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Run post-install scripts
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini $PHP_INI_DIR/conf.d/app.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
