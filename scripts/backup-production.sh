#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
BACKUP_DIR="${BACKUP_DIR:-${REPO_ROOT}/backups}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}. Copia .env.production.example a .env.production antes de ejecutar backups." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

mkdir -p "${BACKUP_DIR}"

echo "[1/3] Exportando base de datos MySQL..."
mysqldump -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --single-transaction --routines --events "${MYSQL_DATABASE}" > "${BACKUP_DIR}/mysql-${MYSQL_DATABASE}-${TIMESTAMP}.sql"

echo "[2/3] Generando backup del contenido de uploads..."
tar -czf "${BACKUP_DIR}/wp-content-uploads-${TIMESTAMP}.tar.gz" -C "${REPO_ROOT}" wp-content/uploads

echo "[3/3] Guardando configuración relevante..."
tar -czf "${BACKUP_DIR}/wp-config-and-customizations-${TIMESTAMP}.tar.gz" -C "${REPO_ROOT}" \
  docker-compose.prod.yml \
  .env.production.example \
  plugins/tanish-inventory \
  themes/tanish-storefront \
  caddy/Caddyfile

echo "Backups creados en ${BACKUP_DIR}"
