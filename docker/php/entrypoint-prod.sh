#!/bin/sh
# Production entrypoint: fail-close boot checks, wait for the database, run
# pending migrations, then hand over to Apache.
set -eu

# Refuse to boot without a real JWT secret in production. The framework's
# GuardedJwtSecretResolver would fail every request anyway (fail-close); this
# check just surfaces the misconfiguration at boot time instead.
if [ "${APP_ENV:-production}" = "production" ] && [ -z "${NENE2_LOCAL_JWT_SECRET:-}" ]; then
    echo "FATAL: NENE2_LOCAL_JWT_SECRET is required in production (fail-close)." >&2
    exit 1
fi

echo "Waiting for the database..."
tries=0
until php -r '
    require "vendor/autoload.php";
    $config = (new Nene2\Config\ConfigLoader(getcwd()))->load();
    (new Nene2\Database\PdoConnectionFactory($config->database))->create();
' 2>/dev/null; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "FATAL: database not reachable after ${tries} attempts." >&2
        exit 1
    fi
    sleep 2
done
echo "Database is up."

echo "Running migrations..."
php vendor/bin/phinx migrate -c phinx.php

exec "$@"
