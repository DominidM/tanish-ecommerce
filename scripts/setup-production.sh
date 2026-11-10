#!/usr/bin/env bash
# Instala en el contenedor de WordPress los componentes que NO viven en el
# repositorio (por eso no se pierden con un `git pull`):
#
#   1. Plugin oficial "Mercado Pago payments for WooCommerce".
#   2. Traducciones de WooCommerce en espanol (el sitio usa el locale es_PE y
#      WordPress.org solo publica es_ES para WooCommerce, asi que se renombran).
#   3. Permisos de escritura de wp-content/uploads (el plugin de Mercado Pago
#      necesita crear sus logs en uploads/wc-logs).
#
# Es idempotente: se puede ejecutar tantas veces como haga falta.
#
# Uso (desde la raiz del repo, en el VPS):
#   ./scripts/setup-production.sh
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.production}"
COMPOSE_FILE="${COMPOSE_FILE:-${REPO_ROOT}/docker-compose.prod.yml}"

MP_PLUGIN_SLUG="woocommerce-mercadopago"
MP_PLUGIN_VERSION="8.10.0"
WC_VERSION="11.1.0"
WC_LOCALE="es_ES"          # idioma origen publicado por WordPress.org
WP_LOCALE="es_PE"          # locale real del sitio (WordPress busca por este nombre)

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Falta ${ENV_FILE}. Copia .env.production.example a .env.production y rellena los valores reales." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "${ENV_FILE}"
set +a

cd "${REPO_ROOT}"

dc() { docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" "$@"; }
WP_CONTAINER="${WORDPRESS_CONTAINER:-tanish-prod-wordpress}"
WP_CONTENT="/var/www/html/wp-content"

echo "[1/4] Descargando el plugin de Mercado Pago ${MP_PLUGIN_VERSION}..."
if dc exec -T "${WP_CONTAINER}" test -f "${WP_CONTENT}/plugins/${MP_PLUGIN_SLUG}/${MP_PLUGIN_SLUG}.php"; then
  echo "      ya instalado, se omite la descarga"
else
  tmp_zip="$(mktemp -d)/${MP_PLUGIN_SLUG}.zip"
  curl -fsSL -o "${tmp_zip}" \
    "https://downloads.wordpress.org/plugin/${MP_PLUGIN_SLUG}.${MP_PLUGIN_VERSION}.zip"

  # Python3 viene en el host; no hay 'unzip' instalado.
  python3 - "${tmp_zip}" <<'PY'
import sys, zipfile
with zipfile.ZipFile(sys.argv[1]) as z:
    z.extractall('/tmp/mp-plugin-extract')
PY

  dc exec -T "${WP_CONTAINER}" mkdir -p "${WP_CONTENT}/plugins/${MP_PLUGIN_SLUG}"
  docker cp "/tmp/mp-plugin-extract/${MP_PLUGIN_SLUG}/." \
    "${WP_CONTAINER}:${WP_CONTENT}/plugins/${MP_PLUGIN_SLUG}/"
  dc exec -T "${WP_CONTAINER}" chown -R www-data:www-data "${WP_CONTENT}/plugins/${MP_PLUGIN_SLUG}"
  rm -rf /tmp/mp-plugin-extract
  echo "      instalado"
fi

echo "[2/4] Activando el plugin de Mercado Pago..."
dc exec -T "${WP_CONTAINER}" php -r "
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
\$plugin = '${MP_PLUGIN_SLUG}/${MP_PLUGIN_SLUG}.php';
if ( in_array( \$plugin, get_option( 'active_plugins' ), true ) ) {
    echo '      ya estaba activo' . PHP_EOL;
    exit;
}
\$res = activate_plugin( \$plugin );
echo is_wp_error( \$res ) ? 'ERROR: ' . \$res->get_error_message() . PHP_EOL : '      activado' . PHP_EOL;
"

echo "[3/4] Instalando traducciones de WooCommerce ${WC_LOCALE} -> ${WP_LOCALE}..."
# WordPress busca los archivos como {dominio}-{locale}.mo y
# {dominio}-{locale}-{md5}.json (uno por cada script de los bloques).
lang_dir="$(mktemp -d)"
curl -fsSL -o "${lang_dir}/wc.zip" \
  "https://downloads.wordpress.org/translation/plugin/woocommerce/${WC_VERSION}/${WC_LOCALE}.zip"

python3 - "${lang_dir}/wc.zip" "${lang_dir}" "${WC_LOCALE}" "${WP_LOCALE}" <<'PY'
import sys, zipfile, os, glob
zip_path, work, src_locale, dst_locale = sys.argv[1:5]
with zipfile.ZipFile(zip_path) as z:
    z.extractall(work)
for path in glob.glob(os.path.join(work, '*')):
    name = os.path.basename(path)
    if f'-{src_locale}' not in name:
        continue
    new = name.replace(f'-{src_locale}', f'-{dst_locale}')
    if new != name:
        os.rename(path, os.path.join(work, new))
print('      archivos renombrados a', dst_locale)
PY

dc exec -T "${WP_CONTAINER}" mkdir -p "${WP_CONTENT}/languages/plugins"
for f in "${lang_dir}"/*; do
  docker cp "$f" "${WP_CONTAINER}:${WP_CONTENT}/languages/plugins/$(basename "$f")"
done
dc exec -T "${WP_CONTAINER}" sh -c "chown www-data:www-data ${WP_CONTENT}/languages/plugins/* && chmod 644 ${WP_CONTENT}/languages/plugins/*"
rm -rf "${lang_dir}"

echo "[4/4] Corrigiendo permisos de wp-content/uploads (logs de Mercado Pago)..."
dc exec -T "${WP_CONTAINER}" sh -c "
  chown -R www-data:www-data ${WP_CONTENT}/uploads
  mkdir -p ${WP_CONTENT}/uploads/wc-logs
  chown www-data:www-data ${WP_CONTENT}/uploads/wc-logs
"

echo
echo "Listo. Verifica el estado con:"
echo "  docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} exec -T ${WORDPRESS_CONTAINER:-tanish-prod-wordpress} \\"
echo "    php -r \"require '/var/www/html/wp-load.php'; print_r(get_option('active_plugins'));\""
echo
echo "Recordatorio: las credenciales de Mercado Pago se configuran en el admin"
echo "(WooCommerce > Ajustes > Mercado Pago) o por opciones de WordPress:"
echo "  _mp_public_key_prod, _mp_access_token_prod, _mp_client_id, _mp_client_secret,"
echo "  _site_id_v1=MPE y checkbox_checkout_test_mode=no (produccion)."
