#!/bin/sh
# Compara dos directorios ignorando diferencias de fin de linea (CRLF vs LF).
# Uso: sh compare-dirs-eol.sh <dir_backup> <dir_repo>

A="$1"
B="$2"

echo "=== Comparacion IGNORANDO CRLF/LF ==="
DISTINTOS=0
IGUALES_EOL=0

# recorrer todos los archivos de A
find "$A" -type f | while read -r fa; do
  rel="${fa#$A/}"
  fb="$B/$rel"

  if [ ! -f "$fb" ]; then
    echo "  SOLO EN BACKUP: $rel"
    continue
  fi

  ma=$(tr -d '\r' < "$fa" | md5sum | cut -d' ' -f1)
  mb=$(tr -d '\r' < "$fb" | md5sum | cut -d' ' -f1)

  if [ "$ma" != "$mb" ]; then
    echo "  CONTENIDO REAL DIFERENTE: $rel"
  fi
done

echo ""
echo "=== Archivos presentes en REPO pero no en BACKUP ==="
find "$B" -type f | while read -r fb; do
  rel="${fb#$B/}"
  fa="$A/$rel"
  if [ ! -f "$fa" ]; then
    echo "  SOLO EN REPO: $rel"
  fi
done
