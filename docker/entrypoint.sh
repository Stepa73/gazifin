#!/bin/bash
set -e

cd /var/www/html

rm -f public/hot

# Jen pro SQLite — u MySQL je DB_DATABASE název databáze, ne cesta k souboru.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    DB_DIR="$(dirname "$DB_FILE")"
    mkdir -p "$DB_DIR"
    if [ ! -f "$DB_FILE" ]; then
        touch "$DB_FILE"
    fi
    chown -R www-data:www-data "$DB_DIR"
fi

# Externí MySQL nemusí být hned po startu stacky dostupná — chvíli počkáme.
attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 15 ]; then
        echo "Databáze není dostupná ani po 15 pokusech, končím." >&2
        exit 1
    fi
    echo "Databáze zatím nedostupná, zkouším znovu ($attempt/15)..."
    attempt=$((attempt + 1))
    sleep 4
done

php artisan db:seed --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
