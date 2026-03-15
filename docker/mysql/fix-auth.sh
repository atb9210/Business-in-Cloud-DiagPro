#!/bin/sh
set -e

echo "[mysql-fix] Waiting for MySQL to be ready..."
until mysqladmin ping -h mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" --silent 2>/dev/null; do
    sleep 2
done

echo "[mysql-fix] Fixing auth plugin for user ${MYSQL_USER}..."
mysql -h mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOF
ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';
FLUSH PRIVILEGES;
SET GLOBAL host_cache_size=0;
EOF

echo "[mysql-fix] Done."
