#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# cPanel shared-hosting deploy script for the KSA Embassy App.
#
# Invoked by .cpanel.yml on every "Update from Remote → Deploy HEAD Commit".
# Safe to run repeatedly. First deploy builds the database from scratch; every
# later deploy only runs new migrations and re-seeds idempotently — live data
# is never dropped.
#
# Override the PHP binary if your host needs a specific version, e.g.:
#   PHP_BIN=/usr/local/bin/ea-php82 bash deploy.sh
# ─────────────────────────────────────────────────────────────────────────────
set -e

# Run from the directory that contains this script (the project root).
cd "$(dirname "$0")"

PHP_BIN="${PHP_BIN:-php}"
LOCK_FILE="storage/app/live-installed.lock"

echo "==> Deploy started in: $(pwd)"
echo "==> Using PHP: $($PHP_BIN -v | head -n 1)"

# 1) Composer — global `composer` is not available on this host, use composer.phar.
if [ ! -f "composer.phar" ]; then
    echo "==> composer.phar not found, downloading..."
    $PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN composer-setup.php --quiet
    rm -f composer-setup.php
fi

echo "==> Installing PHP dependencies (no-dev)..."
$PHP_BIN composer.phar install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 2) Clear config ONLY (never cache:clear here — the cache table may not exist yet).
echo "==> Clearing cached config..."
$PHP_BIN artisan config:clear

# 3) + 4) Migrations and seeders.
# ProductionSeeder only runs the idempotent essentials (roles, plans, super
# admin) so it is safe to call on every deploy without touching live data.
SEED_CLASS="Database\\Seeders\\ProductionSeeder"
if [ ! -f "$LOCK_FILE" ]; then
    echo "==> FIRST DEPLOY: building database from scratch (migrate:fresh)..."
    $PHP_BIN artisan migrate:fresh --force
    echo "==> Seeding initial data..."
    $PHP_BIN artisan db:seed --class="$SEED_CLASS" --force
    mkdir -p storage/app
    echo "Installed on $(date)" > "$LOCK_FILE"
    echo "==> Lock file created: $LOCK_FILE"
else
    echo "==> Existing install detected: running pending migrations only..."
    $PHP_BIN artisan migrate --force
    echo "==> Re-seeding essentials (idempotent — existing data preserved)..."
    $PHP_BIN artisan db:seed --class="$SEED_CLASS" --force
fi

# 5) Storage symlink (ignore failure if the link already exists).
echo "==> Linking storage..."
$PHP_BIN artisan storage:link || true

# 6) Optimize: cache config, routes and views for production.
echo "==> Caching config/routes/views..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 7) Permission fix for shared hosting.
echo "==> Fixing storage/cache permissions..."
chmod -R 775 storage bootstrap/cache || true

echo "==> Deploy finished successfully."
