#!/usr/bin/env bash
# Backup de producción para TANISH (VPS con Docker).
# Correr en el VPS, desde la raíz del repo: ./scripts/backup-production.sh
# Exporta: MySQL (desde el contenedor), wp-content/uploads (desde el volumen)
# y el código/config custom. NO incluye .env.production (secretos -> gestor de contraseñas).
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
BACKUP_DIR="${BACKUP_DIR:-${REPO_ROOT}/backups}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

# Nombres de contenedor/volumen del stack de producción (docker-compose.prod.yml).
MYSQL_CONTAINER="${MYSQL_CONTAINER:-tanish-prod-mysql}"
WORDPRESS_CONTAINER="${WORDPRESS_CONTAINER:-tanish-prod-wordpress}"
WORDPRESS_VOLUME="${WORDPRESS_VOLUME:-tanish_wordpress_data_prod}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}. Copia .env.production.example a .env.production antes de ejecutar backups." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

mkdir -p "${BACKUP_DIR}"

echo "[1/3] Exportando base de datos MySQL (contenedor ${MYSQL_CONTAINER})..."
docker exec "${MYSQL_CONTAINER}" mysqldump \
  -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" \
  --single-transaction --routines --events --skip-comments \
  "${MYSQL_DATABASE}" | gzip > "${BACKUP_DIR}/mysql-${MYSQL_DATABASE}-${TIMESTAMP}.sql.gz"

echo "[2/3] Generando backup de uploads (volumen ${WORDPRESS_VOLUME})..."
docker run --rm \
  -v "${WORDPRESS_VOLUME}:/src:ro" \
  -v "${BACKUP_DIR}:/backup" \
  alpine:3 tar -czf "/backup/wp-content-uploads-${TIMESTAMP}.tar.gz" -C /src wp-content/uploads

echo "[3/3] Guardando configuración y código custom..."
tar -czf "${BACKUP_DIR}/config-and-customizations-${TIMESTAMP}.tar.gz" -C "${REPO_ROOT}" \
  docker-compose.prod.yml \
  .env.production.example \
  plugins/tanish-inventory \
  themes/tanish-storefront \
  caddy/Caddyfile

echo "Backups creados en ${BACKUP_DIR}:"
ls -lh "${BACKUP_DIR}" | tail -n +2
echo
echo "IMPORTANTE: copia estos archivos FUERA del VPS (PC, Drive, bucket)."
echo "Los snapshots de Contabo no reemplazan un backup externo."
