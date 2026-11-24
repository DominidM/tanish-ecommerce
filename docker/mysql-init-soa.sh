#!/bin/sh
# ---------------------------------------------------------------------------
# Crea el esquema y el usuario exclusivo del servicio Laravel.
# Se ejecuta solo la primera vez que se inicializa el volumen de MySQL.
# ---------------------------------------------------------------------------
set -eu

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${LARAVEL_DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${LARAVEL_DB_USERNAME}'@'%' IDENTIFIED BY '${LARAVEL_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${LARAVEL_DB_DATABASE}\`.* TO '${LARAVEL_DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL
