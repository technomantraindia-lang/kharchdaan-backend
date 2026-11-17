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
# Includes sponsor_cashback_amount on cashback_eligibilities and all pending MLM migrations.
echo "Running database migrations..."
if ! php artisan migrate --force; then
    echo "WARNING: migrate reported errors — check logs. Continuing startup."
fi

echo "Running database seeders..."
php artisan db:seed --force || true

echo "Migration status (latest):"
php artisan migrate:status 2>/dev/null | tail -n 20 || true

echo "=================================================="
echo "KharchDaan Backend starting on 0.0.0.0:${PORT}"
echo "=================================================="

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
