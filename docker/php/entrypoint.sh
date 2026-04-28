#!/bin/sh
set -e

cd /var/www

mkdir -p var/cache var/log

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --prefer-dist --no-interaction --no-progress
fi

if [ "${APP_AUTO_MIGRATE:-1}" = "1" ] && [ -f bin/console ]; then
    echo "Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
