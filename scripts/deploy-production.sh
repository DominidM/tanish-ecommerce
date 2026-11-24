#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
SOA_ENV_FILE="${SOA_ENV_FILE:-${REPO_ROOT}/.env.soa}"
COMPOSE_FILE="${COMPOSE_FILE:-${REPO_ROOT}/docker-compose.prod.yml}"
SOA_COMPOSE_FILE="${SOA_COMPOSE_FILE:-${REPO_ROOT}/docker-compose.soa.yml}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}. Copia .env.production.example a .env.production y rellena los valores reales." >&2
  exit 1
fi

COMPOSE_ENV_ARGS=(--env-file "${ENV_FILE}")
COMPOSE_FILE_ARGS=(-f "${COMPOSE_FILE}")
USE_SOA=0

if [[ -f "${SOA_ENV_FILE}" && -f "${SOA_COMPOSE_FILE}" ]]; then
  COMPOSE_ENV_ARGS+=(--env-file "${SOA_ENV_FILE}")
  COMPOSE_FILE_ARGS+=(-f "${SOA_COMPOSE_FILE}")
  USE_SOA=1
fi

# shellcheck disable=SC1090
set -a
source "${ENV_FILE}"
if [[ "${USE_SOA}" -eq 1 ]]; then
  # shellcheck disable=SC1090
  source "${SOA_ENV_FILE}"
fi
set +a

cd "${REPO_ROOT}"

echo "[1/6] Validando configuración de Docker Compose..."
docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" config >/tmp/tanish-compose-check.txt

echo "[2/6] Descargando cambios del repositorio..."
if [[ -d .git ]]; then
  git pull --ff-only
fi

echo "[3/6] Levantando servicios (SOA=${USE_SOA})..."
UP_ARGS=(-d --remove-orphans)
if [[ "${USE_SOA}" -eq 1 ]]; then
  UP_ARGS+=(--build)
fi
docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" up "${UP_ARGS[@]}"

if [[ "${USE_SOA}" -eq 1 ]]; then
  echo "[4/6] Aplicando migraciones y usuarios de Laravel..."
  docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" exec -T laravel php artisan migrate --force
  docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" exec -T laravel php artisan db:seed --force || true
else
  echo "[4/6] Sin overlay SOA: se omite Laravel."
fi

echo "[5/6] Verificando estado de los servicios..."
docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" ps

echo "[6/6] Verificando salud..."
docker compose "${COMPOSE_ENV_ARGS[@]}" "${COMPOSE_FILE_ARGS[@]}" exec -T mysql mysqladmin ping -h 127.0.0.1 -uroot -p"${MYSQL_ROOT_PASSWORD}" --silent
if [[ "${USE_SOA}" -eq 1 && -n "${LARAVEL_URL:-}" ]]; then
  curl -fsSL "${LARAVEL_URL}/up" >/dev/null || true
fi

CADDY_HOST="${CADDY_DOMAIN:-localhost}"
CADDY_HOST="${CADDY_HOST#http://}"
CADDY_HOST="${CADDY_HOST#https://}"
curl -fsSL "http://${CADDY_HOST}/" >/dev/null || curl -fsSL "http://127.0.0.1/" >/dev/null || true

echo "Despliegue listo. Revisa logs con:"
echo "  docker compose ${COMPOSE_ENV_ARGS[*]} ${COMPOSE_FILE_ARGS[*]} logs -f"
