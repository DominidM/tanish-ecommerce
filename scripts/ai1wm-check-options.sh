#!/bin/sh
# Consulta las opciones clave de WordPress tras una restauracion.
# Uso: sh ai1wm-check-options.sh <db> [usuario]

DB="${1:-tanish_wordpress}"
USER="${2:-root}"

mysql -h mysql -u "$USER" -p"$MYSQL_PWD" --batch --vertical "$DB" <<-SQL 2>/dev/null
SELECT option_name, option_value
FROM wp_options
WHERE option_name IN (
  'template',
  'stylesheet',
  'current_theme',
  'active_plugins',
  'WPLANG',
  'woocommerce_coming_soon',
  'blogname'
);
SQL
