#!/bin/sh
set -e

PORT="${PORT:-10000}"

# If APP_KEY is not set or empty, generate one
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set, generating one for runtime..."
    php artisan key:generate --force || true
fi

# Ensure SQLite database file exists if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p database
    touch database/database.sqlite
    chmod 666 database/database.sqlite || true
fi

# Run database migrations & seed default data (admin, roles, permissions)
echo "Running database migrations and seeders..."
php artisan migrate --force --seed || php artisan migrate --force || true

echo "=================================================="
echo "KharchDaan Backend starting on 0.0.0.0:${PORT}"
echo "=================================================="

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
