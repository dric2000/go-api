#!/bin/sh
set -e

php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Combined in a single Render service to stay within the free tier's
# pooled instance-hours (running web + worker as two services would
# need roughly double the free monthly allowance).
php artisan queue:work --tries=3 --sleep=3 &

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
