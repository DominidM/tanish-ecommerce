#!/bin/sh
# Restaura el database.sql de un backup de All-in-One WP Migration.
#
# El volcado de AI1WM usa SERVMASK_PREFIX_ como placeholder del prefijo de
# tablas, asi que hay que reemplazarlo por el prefijo real (wp_) antes de
# importar.
#
# Uso: sh ai1wm-restore-db.sh <ruta/database.sql> <db> <usuario>
#   El password se toma de la variable de entorno MYSQL_PWD.

SQL="$1"
DB="$2"
USER="$3"

if [ -z "$SQL" ] || [ -z "$DB" ] || [ -z "$USER" ]; then
  echo "Uso: sh ai1wm-restore-db.sh <database.sql> <db> <usuario>"
  exit 1
fi

if [ ! -f "$SQL" ]; then
  echo "No existe el archivo: $SQL"
  exit 1
fi

WORK=/tmp/ai1wm-restore
mkdir -p "$WORK"

echo "=== 1. Reemplazando placeholder de prefijo ==="
ORIG=$(grep -c 'SERVMASK_PREFIX_' "$SQL")
sed 's/SERVMASK_PREFIX_/wp_/g' "$SQL" > "$WORK/database-prefixed.sql"
NEW=$(grep -c 'wp_' "$WORK/database-prefixed.sql")
echo "Ocurrencias SERVMASK_PREFIX_ originales: $ORIG"
echo "Verificacion (CREATE TABLE con prefijo wp_): $(grep -c 'CREATE TABLE .wp_' "$WORK/database-prefixed.sql")"

echo ""
echo "=== 2. Tablas actuales en la base ==="
mysql -h mysql -u "$USER" -p"$MYSQL_PWD" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB';" 2>/dev/null

echo ""
echo "=== 3. Importando volcado ==="
# El volcado puede traer valores por defecto '0000-00-00 00:00:00' que el modo
# estricto de MySQL 8 rechaza (ERROR 1067). Se relaja sql_mode solo para esta
# sesion de importacion, sin tocar la configuracion del servidor.
SQL_MODE_INIT="SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'"

if mysql -h mysql -u "$USER" -p"$MYSQL_PWD" \
      --default-character-set=utf8mb4 \
      --init-command="$SQL_MODE_INIT" \
      "$DB" < "$WORK/database-prefixed.sql" 2>"$WORK/import-error.log"; then
  echo "Importacion OK"
else
  echo "ERROR en la importacion. Primeras lineas de error:"
  head -20 "$WORK/import-error.log"
  exit 2
fi

echo ""
echo "=== 4. Tablas tras la importacion ==="
mysql -h mysql -u "$USER" -p"$MYSQL_PWD" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB';" 2>/dev/null

echo ""
echo "=== 5. Verificacion rapida ==="
mysql -h mysql -u "$USER" -p"$MYSQL_PWD" -N -e \
  "SELECT 'users'    AS t, COUNT(*) FROM \`$DB\`.wp_users
   UNION ALL SELECT 'posts',    COUNT(*) FROM \`$DB\`.wp_posts
   UNION ALL SELECT 'products', COUNT(*) FROM \`$DB\`.wp_posts WHERE post_type='product'
   UNION ALL SELECT 'inventario', COUNT(*) FROM \`$DB\`.wp_tanish_inventory_movements;" 2>/dev/null

mysql -h mysql -u "$USER" -p"$MYSQL_PWD" -N -e \
  "SELECT CONCAT('siteurl: ', option_value) FROM \`$DB\`.wp_options WHERE option_name='siteurl';
   SELECT CONCAT('home:    ', option_value) FROM \`$DB\`.wp_options WHERE option_name='home';
   SELECT CONCAT('tema:    ', option_value) FROM \`$DB\`.wp_options WHERE option_name='template';" 2>/dev/null
