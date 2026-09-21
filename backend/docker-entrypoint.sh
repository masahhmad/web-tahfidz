#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --ansi --force
fi

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

php artisan migrate --force

exec "$@"
