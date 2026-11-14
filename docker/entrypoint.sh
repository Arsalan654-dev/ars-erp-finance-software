#!/bin/sh
set -eu

APP_DIR=/app

mkdir -p \
    /tmp/storage/framework/views \
    /tmp/storage/framework/cache \
    /tmp/storage/framework/sessions \
    /tmp/storage/logs \
    /tmp/storage/app/public \
    /tmp/bootstrap-cache

rm -rf "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ln -s /tmp/storage "$APP_DIR/storage"
ln -s /tmp/bootstrap-cache "$APP_DIR/bootstrap/cache"

cp "$APP_DIR/database/demo.sqlite" /tmp/database.sqlite

if [ -n "${ERP_SEED_PASSWORD:-}" ]; then
    php "$APP_DIR/docker/update-demo-passwords.php" /tmp/database.sqlite "$ERP_SEED_PASSWORD"
fi

export DB_DATABASE=/tmp/database.sqlite
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

php "$APP_DIR/artisan" config:clear >/dev/null 2>&1 || true
php "$APP_DIR/artisan" config:cache >/dev/null 2>&1 || true

exec php -S "0.0.0.0:${PORT:-80}" -t "$APP_DIR" "$APP_DIR/docker/router.php"
