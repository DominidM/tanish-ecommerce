#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${REPO_ROOT}/.env"
CONTAINER="tanish-mysql"

usage() {
  echo "Uso: $0 <archivo.sql.gz|archivo.sql>"
  echo ""
  echo "Restaura un backup de la base de datos de TANISH."
  echo ""
  echo "Ejemplo:"
  echo "  $0 backups/tanish_backup_20260914_120000.sql.gz"
  exit 1
}

if [[ $# -lt 1 ]]; then
  usage
fi

DUMP_FILE="$1"

if [[ ! -f "${DUMP_FILE}" ]]; then
  echo "Error: El archivo '${DUMP_FILE}' no existe." >&2
  exit 1
fi

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Error: Falta ${ENV_FILE}. Copia .env.example a .env y configura las credenciales." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  echo "Error: El contenedor ${CONTAINER} no esta corriendo. Ejecuta: docker compose up -d" >&2
  exit 1
fi

echo "Archivo a importar: ${DUMP_FILE}"
echo "Base de datos: ${MYSQL_DATABASE}"
echo ""

read -p "Esto REEMPLAZARA toda la base de datos actual. Continuar? (s/N) " -r
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
  echo "Cancelado."
  exit 0
fi

echo ""
echo "[1/4] Deteniendo WordPress..."
docker compose -f "${REPO_ROOT}/docker-compose.yml" stop wordpress

echo "[2/4] Eliminando base de datos existente..."
docker exec "${CONTAINER}" mysql \
  -u"root" \
  -p"${MYSQL_ROOT_PASSWORD}" \
  -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "[3/4] Importando backup..."
if [[ "${DUMP_FILE}" == *.gz ]]; then
  gunzip -c "${DUMP_FILE}" | docker exec -i "${CONTAINER}" mysql \
    -u"${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    "${MYSQL_DATABASE}"
else
  docker exec -i "${CONTAINER}" mysql \
    -u"${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    "${MYSQL_DATABASE}" < "${DUMP_FILE}"
fi

echo "[4/4] Iniciando WordPress..."
docker compose -f "${REPO_ROOT}/docker-compose.yml" start wordpress

echo ""
echo "Restore completado. WordPress estara disponible en http://localhost:8080 en unos segundos."
echo "Si WordPress no responde, reinicia con: docker compose restart wordpress"
