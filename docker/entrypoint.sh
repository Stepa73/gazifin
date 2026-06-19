#!/bin/bash
set -e

cd /var/www/html

rm -f public/hot

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

php artisan migrate --force
php artisan db:seed --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
