#!/usr/bin/env bash
# Mise à jour de l'API : bash /var/www/go-api/deploy/deploy-api.sh
set -euo pipefail
cd /var/www/go-api

git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize

sudo systemctl reload php8.3-fpm
sudo systemctl restart go-queue
echo "API à jour."
