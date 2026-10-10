#!/usr/bin/env sh
set -eu

# Railway's Postgres template exports DATABASE_URL; this app uses Laravel's DB_URL.
if [ -z "${DB_URL:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    export DB_URL="$DATABASE_URL"
fi

# Use a generated key only as a fallback; production should keep APP_KEY set in Railway.
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
elif [ "${APP_KEY#base64:}" = "${APP_KEY}" ]; then
    export APP_KEY="base64:${APP_KEY}"
fi

php artisan migrate --force --seed
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

port="${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${port}"
