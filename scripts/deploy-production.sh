#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
COMPOSE_FILE="${COMPOSE_FILE:-${REPO_ROOT}/docker-compose.prod.yml}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}. Copia .env.production.example a .env.production y rellena los valores reales." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "${ENV_FILE}"
set +a

cd "${REPO_ROOT}"

echo "[1/4] Validando configuración de Docker Compose..."
docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" config >/tmp/tanish-compose-check.txt

echo "[2/4] Descargando cambios del repositorio..."
if [[ -d .git ]]; then
  git pull --ff-only
fi

echo "[3/4] Levantando servicios..."
docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" up -d --remove-orphans

echo "[4/4] Verificando salud..."
docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" ps

docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" exec -T mysql mysqladmin ping -h 127.0.0.1 -uroot -p"${MYSQL_ROOT_PASSWORD}" --silent
curl -fsSL "http://${CADDY_DOMAIN:-localhost}/" >/dev/null || curl -fsSL "http://127.0.0.1/" >/dev/null || true

echo "Despliegue listo. Revisa logs con: docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} logs -f"
