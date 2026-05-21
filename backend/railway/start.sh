#!/usr/bin/env bash
set -euo pipefail

php artisan config:clear
php artisan cache:clear
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"