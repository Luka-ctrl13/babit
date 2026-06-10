#!/usr/bin/env bash
# Initial Ubuntu server setup for Trading Gateway
# Run as root: bash deploy/server-setup.sh

set -euo pipefail

DOMAIN="your-domain.com"
APP_DIR="/var/www/babit"

echo "==> Updating system..."
apt update && apt upgrade -y

echo "==> Installing base packages..."
apt install -y git curl unzip nginx supervisor redis-server certbot python3-certbot-nginx \
    postgresql postgresql-contrib

echo "==> Installing PHP 8.3..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-pgsql php8.3-redis php8.3-bcmath php8.3-intl

echo "==> Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Setting up PostgreSQL..."
sudo -u postgres psql -c "CREATE USER trading WITH PASSWORD 'CHANGE_THIS_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE trading_db OWNER trading;"

echo "==> Cloning application..."
git clone git@github.com:luka-ctrl13/babit.git "$APP_DIR"
cd "$APP_DIR"
cp .env.example .env
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR/storage"
chmod -R 755 "$APP_DIR/bootstrap/cache"

echo "==> Installing app dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

echo "==> Generating app key..."
php artisan key:generate

echo "==> Running migrations & seeders..."
php artisan migrate --force
php artisan db:seed --force

echo "==> Setting up Nginx..."
cp "$APP_DIR/deploy/nginx.conf" /etc/nginx/sites-available/trading-gateway
sed -i "s/your-domain.com/$DOMAIN/g" /etc/nginx/sites-available/trading-gateway
ln -sf /etc/nginx/sites-available/trading-gateway /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

echo "==> Setting up SSL..."
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m admin@"$DOMAIN"

echo "==> Setting up Supervisor..."
cp "$APP_DIR/deploy/supervisor-horizon.conf" /etc/supervisor/conf.d/
supervisorctl reread
supervisorctl update
supervisorctl start trading-horizon

echo "==> Setting up log rotation..."
cat > /etc/logrotate.d/trading-gateway << 'EOF'
/var/www/babit/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 664 www-data www-data
}
EOF

echo "==> Done! Edit /var/www/babit/.env with your API keys then run:"
echo "    php artisan config:cache"
