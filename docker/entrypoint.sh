#!/bin/sh
# Container start-up: make sure there is an app key, wait for MySQL, migrate, optionally seed demo data.
set -e
cd /var/www/html

# Keep a generated APP_KEY in the storage volume so sessions survive restarts.
# (Nothing secret is baked into the image or committed to git.)
KEY_FILE=storage/app/.app_key
if [ -z "$APP_KEY" ]; then
    mkdir -p storage/app
    [ -s "$KEY_FILE" ] || php artisan key:generate --show --no-ansi > "$KEY_FILE"
    export APP_KEY="$(cat "$KEY_FILE")"
fi

echo "Waiting for the database..."
tries=0
until php artisan migrate --force --no-interaction; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "Database did not become ready in time." >&2
        exit 1
    fi
    sleep 2
done

if [ "$SEED_DEMO_DATA" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

chown -R www-data:www-data storage bootstrap/cache
exec docker-php-entrypoint "$@"
