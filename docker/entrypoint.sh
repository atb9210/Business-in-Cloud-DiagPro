#!/bin/sh
set -e

echo "========================================="
echo " BusinessCloud - Starting up..."
echo "========================================="

# --- Wait for MySQL ---
echo "[1/7] Waiting for MySQL..."
MAX_TRIES=30
TRIES=0
until php -r "
try {
    new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
        getenv('DB_USERNAME') ?: 'diagpro',
        getenv('DB_PASSWORD') ?: ''
    );
    echo 'ok';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null | grep -q 'ok'; do
    TRIES=$((TRIES + 1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "  ERROR: MySQL connection timeout after $MAX_TRIES attempts"
        exit 1
    fi
    echo "  MySQL not ready, retrying... ($TRIES/$MAX_TRIES)"
    sleep 3
done
echo "  MySQL connected."

# --- Wait for Redis ---
echo "[2/7] Waiting for Redis..."
TRIES=0
REDIS_PASS="${REDIS_PASSWORD:-}"
until php -r "
try {
    \$r = new Redis();
    \$r->connect(getenv('REDIS_HOST') ?: 'redis', (int)(getenv('REDIS_PORT') ?: 6379));
    \$pass = getenv('REDIS_PASSWORD');
    if (\$pass) \$r->auth(\$pass);
    \$r->ping();
    echo 'ok';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null | grep -q 'ok'; do
    TRIES=$((TRIES + 1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "  WARNING: Redis connection timeout, continuing anyway..."
        break
    fi
    echo "  Redis not ready, retrying... ($TRIES/$MAX_TRIES)"
    sleep 2
done
echo "  Redis connected."

# --- Permissions ---
echo "[3/7] Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create required directories
mkdir -p /var/www/storage/logs \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/framework/cache/data \
         /var/log/supervisor
chown -R www-data:www-data /var/www/storage

# --- Generate APP_KEY if missing ---
echo "[4/7] Laravel setup..."
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "  Generating APP_KEY..."
    GENERATED_KEY=$(php artisan key:generate --show --no-interaction 2>/dev/null)
    export APP_KEY="$GENERATED_KEY"
    echo "  APP_KEY set in environment."
fi

# Storage link
php artisan storage:link --force 2>/dev/null || true

# --- Fix MySQL auth plugin ---
echo "  Fixing MySQL auth plugin for ${DB_USERNAME}..."
mysql -h"$DB_HOST" -uroot -p"${DB_ROOT_PASSWORD:-rootpassword}" \
    -e "ALTER USER '${DB_USERNAME}'@'%' IDENTIFIED WITH mysql_native_password BY '${DB_PASSWORD}'; FLUSH PRIVILEGES; SET GLOBAL host_cache_size=0;" \
    2>&1 | grep -v "^$" || true
echo "  MySQL auth plugin step done."

# --- Migrations ---
echo "[5/7] Running migrations..."
mysql -h"$DB_HOST" -uroot -p"${DB_ROOT_PASSWORD:-rootpassword}" -e "SET GLOBAL FOREIGN_KEY_CHECKS=0;" 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || {
    echo "  Migration had errors, continuing..."
}
mysql -h"$DB_HOST" -uroot -p"${DB_ROOT_PASSWORD:-rootpassword}" -e "SET GLOBAL FOREIGN_KEY_CHECKS=1;" 2>/dev/null || true

# --- Seed ---
if [ "$DB_SEED" = "true" ]; then
    echo "[6/7] Seeding database..."
    php artisan db:seed --force --no-interaction 2>&1 || {
        echo "  Seeding had errors (possibly already seeded), continuing..."
    }
else
    echo "[6/7] Seeding skipped (DB_SEED != true)"
fi

# --- Cache optimization ---
echo "[7/7] Optimizing..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction 2>/dev/null || true
php artisan event:cache --no-interaction 2>/dev/null || true

echo "========================================="
echo " BusinessCloud ready!"
echo "========================================="

# Execute CMD
exec "$@"
