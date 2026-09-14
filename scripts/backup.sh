#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${REPO_ROOT}/.env"
BACKUP_DIR="${REPO_ROOT}/backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
CONTAINER="tanish-mysql"

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

mkdir -p "${BACKUP_DIR}"

DUMP_FILE="${BACKUP_DIR}/tanish_backup_${TIMESTAMP}.sql.gz"

echo "Exportando base de datos ${MYSQL_DATABASE}..."
docker exec "${CONTAINER}" mysqldump \
  -u"${MYSQL_USER}" \
  -p"${MYSQL_PASSWORD}" \
  --single-transaction \
  --routines \
  --events \
  "${MYSQL_DATABASE}" | gzip > "${DUMP_FILE}"

SIZE=$(du -h "${DUMP_FILE}" | cut -f1)
echo "Backup creado: ${DUMP_FILE} (${SIZE})"
echo ""
echo "Para restaurar en otra maquina:"
echo "  ./scripts/restore.sh ${DUMP_FILE}"
