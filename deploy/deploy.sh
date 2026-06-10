#!/usr/bin/env bash
# Deployment script for Trading Gateway
# Run as: bash deploy/deploy.sh

set -euo pipefail

APP_DIR="/var/www/babit"
PHP="php8.3"

echo "==> Pulling latest code..."
cd "$APP_DIR"
git pull origin main

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "==> Running migrations..."
$PHP artisan migrate --force

echo "==> Optimizing..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

echo "==> Restarting Horizon..."
$PHP artisan horizon:terminate
supervisorctl restart trading-horizon

echo "==> Done."
