#!/bin/sh
# Analiza el database.sql extraido de un .wpress
# Uso: sh ai1wm-sql-info.sh /ruta/database.sql

SQL="$1"

echo "=== RESUMEN DEL VOLCADO ==="
echo "Lineas:      $(wc -l < "$SQL")"
echo "CREATE TABLE:$(grep -c 'CREATE TABLE' "$SQL")"
echo "INSERT INTO: $(grep -c 'INSERT INTO' "$SQL")"

echo ""
echo "=== TABLAS ==="
grep 'CREATE TABLE' "$SQL" \
  | sed 's/.*CREATE TABLE //' \
  | sed 's/[`"]//g' \
  | sed 's/SERVMASK_PREFIX_//g' \
  | sed 's/[^a-zA-Z_].*//' \
  | sort \
  | tr '\n' ' '

echo ""
echo ""
echo "=== OPCIONES DE URL EN EL VOLCADO ==="
grep -o "option_value.*localhost[^ ]*" "$SQL" | head -5

echo ""
echo "=== SIETE/USUARIOS ==="
grep -o "user_login[^,]*" "$SQL" | head -10
