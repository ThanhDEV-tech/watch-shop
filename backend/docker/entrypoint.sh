#!/bin/sh
set -eu

if [ ! -f .env ]; then
    cp .env.docker .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

app_key="$(grep '^APP_KEY=' .env | cut -d '=' -f 2- || true)"
if [ -z "$app_key" ]; then
    php artisan key:generate --force
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# The project directory is bind-mounted from the host, so cached Laravel
# configuration may contain native-development values such as DB_HOST=127.0.0.1.
# Clear it before any database work; the queue container shares this directory.
php artisan config:clear

php artisan migrate --force
php artisan storage:link || true

# `artisan serve` deliberately removes most inherited variables when a .env file
# exists, then its child server reloads the host-mounted .env. Run the same PHP
# development server directly so Docker's DB_HOST=mysql remains authoritative.
cd /var/www/html/public
exec php -S 0.0.0.0:8000 /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
