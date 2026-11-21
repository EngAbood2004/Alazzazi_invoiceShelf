#!/bin/bash
cd /var/www/html

mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

# Graceful fallback: if the configured database host cannot be resolved
# (e.g. a Render Postgres instance in another region), run on sqlite/file
# so the service can still start. Fix the DB later and it is used again.
if [ -n "$DB_CONNECTION" ] && [ "$DB_CONNECTION" != "sqlite" ] && [ -n "$DB_HOST" ]; then
    if ! getent hosts "$DB_HOST" >/dev/null 2>&1; then
        echo "DB host '$DB_HOST' is unreachable; falling back to sqlite/file drivers"
        export DB_CONNECTION=sqlite
        export DB_DATABASE=/var/www/html/database/database.sqlite
        export SESSION_DRIVER=file
        export CACHE_STORE=file
        export QUEUE_CONNECTION=sync
    fi
fi

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan migrate --force || true

touch storage/logs/laravel.log || true
chmod -R 775 storage bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache database || true
chmod -R 775 database || true
sqlite_file="database/database.sqlite"
if [ -f "$sqlite_file" ]; then chmod 664 "$sqlite_file" || true; fi

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
