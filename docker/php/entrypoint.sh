#!/bin/sh
set -e

cd /var/www

mkdir -p var/cache var/log var/share

DB_HOST="${DATABASE_HOST:-db}"
DB_PORT="${DATABASE_PORT:-3306}"
DB_USER="${DATABASE_USER:-app}"
DB_PASSWORD="${DATABASE_PASSWORD:-app}"
DB_WAIT_RETRIES="${APP_DB_WAIT_RETRIES:-30}"
DB_WAIT_INTERVAL="${APP_DB_WAIT_INTERVAL:-2}"

if [ "${APP_WAIT_FOR_DB:-1}" = "1" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    attempt=0

    until php -r '
        $host = getenv("DATABASE_HOST") ?: "db";
        $port = getenv("DATABASE_PORT") ?: "3306";
        $user = getenv("DATABASE_USER") ?: "app";
        $password = getenv("DATABASE_PASSWORD") ?: "app";

        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s", $host, $port),
                $user,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        attempt=$((attempt + 1))

        if [ "$attempt" -ge "$DB_WAIT_RETRIES" ]; then
            echo "MySQL did not become ready after ${DB_WAIT_RETRIES} attempts." >&2
            exit 1
        fi

        sleep "$DB_WAIT_INTERVAL"
    done
fi

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --prefer-dist --no-interaction --no-progress
fi

if [ -f bin/console ]; then
    if [ "${APP_AUTO_MIGRATE:-1}" = "1" ] && find migrations -maxdepth 1 -name '*.php' -print -quit | grep -q .; then
        echo "Running migrations..."
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    elif [ "${APP_AUTO_SCHEMA_UPDATE:-1}" = "1" ]; then
        echo "Updating database schema from Doctrine entities..."
        php bin/console doctrine:schema:update --force
    fi
fi

exec "$@"
