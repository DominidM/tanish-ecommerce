#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Prepara el servicio Laravel en producción:
#   - crea el esquema y usuario MySQL exclusivos
#   - genera credenciales de la REST API de WooCommerce
#   - escribe .env.soa con permisos 600
#
# Uso (en el VPS, desde la raíz del repo):
#   TANISH_ADMIN_PASSWORD='...' bash scripts/setup-soa-production.sh
# ---------------------------------------------------------------------------
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
SOA_ENV_FILE="${SOA_ENV_FILE:-${REPO_ROOT}/.env.soa}"

if [[ -f "${SOA_ENV_FILE}" ]]; then
  echo "Ya existe ${SOA_ENV_FILE}. No se sobrescribe."
  exit 0
fi

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "${ENV_FILE}"
set +a

TANISH_ADMIN_PASSWORD="${TANISH_ADMIN_PASSWORD:-}"
if [[ -z "${TANISH_ADMIN_PASSWORD}" ]]; then
  echo "Define TANISH_ADMIN_PASSWORD para crear el administrador." >&2
  exit 1
fi

IP="$(hostname -I | awk '{print $1}')"
CADDY_ADMIN_DOMAIN="${CADDY_ADMIN_DOMAIN:-http://admin.${IP}.nip.io}"
LARAVEL_URL="${LARAVEL_URL:-${CADDY_ADMIN_DOMAIN}}"
LARAVEL_DB_DATABASE="${LARAVEL_DB_DATABASE:-tanish_laravel}"
LARAVEL_DB_USERNAME="${LARAVEL_DB_USERNAME:-tanish_laravel}"
LARAVEL_DB_PASSWORD="${LARAVEL_DB_PASSWORD:-$(openssl rand -hex 16)}"
LARAVEL_APP_KEY="${LARAVEL_APP_KEY:-base64:$(openssl rand -base64 32 | tr -d '\n')}"
WOOCOMMERCE_WEBHOOK_SECRET="${WOOCOMMERCE_WEBHOOK_SECRET:-$(openssl rand -hex 32)}"
WOOCOMMERCE_URL="${WOOCOMMERCE_URL:-http://wordpress:80}"
TANISH_STORE_URL="${TANISH_STORE_URL:-${WORDPRESS_URL:-https://tanish-proxy.solvegrades.workers.dev}}"
TANISH_WHATSAPP_NUMBER="${TANISH_WHATSAPP_NUMBER:-51975852932}"
TANISH_ADMIN_EMAIL="${TANISH_ADMIN_EMAIL:-tanish_admin@tanish.local}"
WHATSAPP_DRIVER="${WHATSAPP_DRIVER:-log}"

echo "[1/4] Creando esquema y usuario MySQL de Laravel..."
docker exec -i tanish-prod-mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${LARAVEL_DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${LARAVEL_DB_USERNAME}'@'%' IDENTIFIED BY '${LARAVEL_DB_PASSWORD}';
ALTER USER '${LARAVEL_DB_USERNAME}'@'%' IDENTIFIED BY '${LARAVEL_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${LARAVEL_DB_DATABASE}\`.* TO '${LARAVEL_DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL

echo "[2/4] Generando credenciales REST de WooCommerce..."
cat > /tmp/tanish-wc-key.php <<'PHP'
<?php
global $wpdb;

$description = 'Laravel SOA ' . gmdate('Y-m-d H:i:s');
$consumer_key = 'ck_' . wc_rand_hash();
$consumer_secret = 'cs_' . wc_rand_hash();

$inserted = $wpdb->insert(
    $wpdb->prefix . 'woocommerce_api_keys',
    [
        'user_id' => 1,
        'description' => $description,
        'permissions' => 'read_write',
        'consumer_key' => wc_api_hash($consumer_key),
        'consumer_secret' => $consumer_secret,
        'truncated_key' => substr($consumer_key, -7),
    ]
);

if (! $inserted) {
    fwrite(STDERR, "No se pudo crear la clave API\n");
    exit(1);
}

echo json_encode(['key' => $consumer_key, 'secret' => $consumer_secret]);
PHP

docker cp /tmp/tanish-wc-key.php tanish-prod-wordpress:/tmp/tanish-wc-key.php

if ! KEY_JSON="$(docker exec -u www-data tanish-prod-wordpress wp eval-file /tmp/tanish-wc-key.php 2>/tmp/tanish-wc-key.err)"; then
  cat /tmp/tanish-wc-key.err >&2
  rm -f /tmp/tanish-wc-key.php /tmp/tanish-wc-key.err
  exit 1
fi

rm -f /tmp/tanish-wc-key.php /tmp/tanish-wc-key.err
docker exec tanish-prod-wordpress rm -f /tmp/tanish-wc-key.php

WOO_KEY="$(printf '%s' "${KEY_JSON}" | sed -n 's/.*"key":"\([^"]*\)".*/\1/p')"
WOO_SECRET="$(printf '%s' "${KEY_JSON}" | sed -n 's/.*"secret":"\([^"]*\)".*/\1/p')"

if [[ -z "${WOO_KEY}" || -z "${WOO_SECRET}" ]]; then
  echo "No se pudieron obtener las credenciales de WooCommerce." >&2
  exit 1
fi

echo "[3/4] Escribiendo ${SOA_ENV_FILE} (permisos 600)..."
umask 077
cat > "${SOA_ENV_FILE}" <<EOF
CADDY_ADMIN_DOMAIN=${CADDY_ADMIN_DOMAIN}
LARAVEL_URL=${LARAVEL_URL}
LARAVEL_APP_KEY=${LARAVEL_APP_KEY}
LARAVEL_DB_DATABASE=${LARAVEL_DB_DATABASE}
LARAVEL_DB_USERNAME=${LARAVEL_DB_USERNAME}
LARAVEL_DB_PASSWORD=${LARAVEL_DB_PASSWORD}
WOOCOMMERCE_URL=${WOOCOMMERCE_URL}
WOOCOMMERCE_KEY=${WOO_KEY}
WOOCOMMERCE_SECRET=${WOO_SECRET}
WOOCOMMERCE_WEBHOOK_SECRET=${WOOCOMMERCE_WEBHOOK_SECRET}
TANISH_STORE_URL=${TANISH_STORE_URL}
TANISH_WHATSAPP_NUMBER=${TANISH_WHATSAPP_NUMBER}
TANISH_ADMIN_EMAIL=${TANISH_ADMIN_EMAIL}
TANISH_ADMIN_PASSWORD=${TANISH_ADMIN_PASSWORD}
WHATSAPP_DRIVER=${WHATSAPP_DRIVER}
EOF
chmod 600 "${SOA_ENV_FILE}"

echo "[4/4] Listo."
echo "  Panel:   ${LARAVEL_URL}/admin"
echo "  Usuario: ${TANISH_ADMIN_EMAIL}"
echo "  Archivo: ${SOA_ENV_FILE}"
echo
echo "Siguiente paso: bash scripts/deploy-production.sh"
