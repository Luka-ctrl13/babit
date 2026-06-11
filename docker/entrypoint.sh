#!/bin/sh
set -e

# Railway/Render inject all config as env vars — no .env file needed.
# But some artisan commands (key:generate, config:cache) still try to
# read/write .env, so we create an empty one if it doesn't exist.
touch /var/www/html/.env

# Railway injects $PORT; default to 80 for other hosts
PORT="${PORT:-80}"

# Inject port into Nginx config
sed "s/NGINX_PORT/${PORT}/g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

cd /var/www/html

# Generate app key only if not provided via env var
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Database migrations
php artisan migrate --force

# Seed default settings (idempotent — uses firstOrCreate)
php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || true

# Cache for performance (reads from env vars, not .env file)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure writable dirs are owned by web user
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
