#!/bin/sh
set -e

PORT="${PORT:-10000}"

# If APP_KEY is not set or empty, generate one
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set, generating one for runtime..."
    php artisan key:generate --force || true
fi

# Run database migrations if DB is configured and accessible
if [ -n "$DB_DATABASE" ]; then
    echo "Running database migrations (if any)..."
    php artisan migrate --force || true
fi

echo "=================================================="
echo "KharchDaan Backend starting on 0.0.0.0:${PORT}"
echo "=================================================="

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
