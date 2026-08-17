#!/usr/bin/env bash
#
# Burrow production deploy script.
#
# Run this from the server after the latest code is already checked out
# (git pull / CI artifact / rsync - whatever gets code onto the box is your
# call, this script doesn't do it). It installs dependencies, builds
# frontend assets, migrates the database, and warms Laravel's caches.
#
# It deliberately does NOT start or restart php-fpm/nginx, `artisan serve`,
# or the scheduler - those are owned by your process manager
# (systemd/Supervisor/pm2/etc) so this script is safe to re-run on every
# deploy without touching them. The two exceptions are `queue:restart`,
# which doesn't start anything itself - it just signals already-running
# queue workers to gracefully restart after their current job so they pick
# up the new code - and the bot process, which this script restarts via pm2
# (process name "burrow-bot") since it's the one long-running process whose
# own dependencies/code this script just updated.
#
# Requires: .env already configured on the server (this script never writes
# .env), composer, node/npm, PHP, and pm2 available on PATH, with the bot
# process already registered under pm2 as "burrow-bot".

set -euo pipefail

git pull 

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing and building dashboard frontend assets"
npm ci
npm run build

echo "==> Installing bot process dependencies"
(cd bot && npm ci --omit=dev)

echo "==> Restarting the bot process"
(cd bot && pm2 restart burrow-bot)

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Linking public storage"
php artisan storage:link

echo "==> Caching configuration, routes, and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Signalling queue workers to restart on their next job"
php artisan queue:restart

echo "==> Deploy complete."
echo "    Reload php-fpm/nginx via your process manager if needed."
echo "    If this is a first deploy, register the bot process with pm2 first:"
echo "    (cd bot && pm2 start src/index.js --name burrow-bot)"
