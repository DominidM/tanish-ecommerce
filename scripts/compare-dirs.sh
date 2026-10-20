#!/bin/sh
# Compara dos directorios y reporta diferencias.
# Uso: sh compare-dirs.sh <dir_a> <dir_b>

A="$1"
B="$2"

if [ ! -d "$A" ] || [ ! -d "$B" ]; then
  echo "Ambos argumentos deben ser directorios existentes."
  exit 1
fi

echo "=== Archivos solo en A: $A ==="
diff -rq "$A" "$B" 2>/dev/null | grep '^Files' | wc -l | sed 's/^/Archivos distintos: /'
diff -rq "$A" "$B" 2>/dev/null | grep '^Only in '"$A" | sed 's/^/  /'

echo ""
echo "=== Archivos solo en B: $B ==="
diff -rq "$A" "$B" 2>/dev/null | grep '^Only in '"$B" | sed 's/^/  /'

echo ""
echo "=== Archivos con contenido distinto ==="
diff -rq "$A" "$B" 2>/dev/null | grep '^Files' | sed 's/^/  /'

echo ""
echo "Total de diferencias reportadas: $(diff -rq "$A" "$B" 2>/dev/null | wc -l)"
