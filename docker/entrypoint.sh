#!/bin/sh
set -e

# Default PORT to 80 if not set (Railway provides $PORT)
export PORT="${PORT:-80}"

# Substitute $PORT into Nginx config
envsubst '$PORT' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf

cd /var/www/html

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Seed default settings if first boot
php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || true

# Cache config/routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix storage permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
