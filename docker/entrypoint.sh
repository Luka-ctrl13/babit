#!/bin/sh
set -e

# Railway injects $PORT; default to 80 for other hosts
PORT="${PORT:-80}"

# Inject port into Nginx config (we use a plain placeholder, not $VAR, to
# avoid envsubst accidentally replacing Nginx's own $uri/$args variables)
sed "s/NGINX_PORT/${PORT}/g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

cd /var/www/html

# Generate app key if not provided via env
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Database migrations
php artisan migrate --force

# Seed default settings on first boot (idempotent — uses firstOrCreate)
php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || true

# Cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure writable dirs are owned by web user
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
