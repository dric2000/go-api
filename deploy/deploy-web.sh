#!/usr/bin/env bash
# Mise à jour du site : bash /var/www/go-api/deploy/deploy-web.sh
set -euo pipefail
cd /var/www/go-web

git pull --ff-only
npm ci
npm run build
sudo systemctl restart go-web
echo "Site à jour."
