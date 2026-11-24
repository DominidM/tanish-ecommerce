# TANISH — Servicio Laravel (tienda + panel + integración)

Segundo servicio de la arquitectura SOA de TANISH. **No reemplaza** a WordPress ni a WooCommerce: los consume por **REST API v3** y **webhooks firmados**.

```
WordPress + WooCommerce  ──webhooks/REST──▶  Laravel
 (comercio, stock, pedidos)                   tienda pública + panel + kardex
```

## Rutas

| Ruta | Descripción |
|---|---|
| `/` | Tienda pública (home) |
| `/tienda` | Catálogo con búsqueda y categorías |
| `/producto/{slug}` | Ficha de producto |
| `/cotizacion` | Cotización en sesión |
| `/cotizacion/whatsapp` | Envía la cotización por WhatsApp |
| `/admin` | Panel Filament |
| `/api/webhooks/woocommerce` | Webhook firmado de WooCommerce |
| `/up` | Healthcheck |

## Requisitos

- PHP 8.3+
- Composer 2
- SQLite (desarrollo) o MySQL 8 (producción)
- Redis (producción)

## Puesta en marcha local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

- Tienda: <http://localhost:8000/>
- Panel: <http://localhost:8000/admin>
- Admin local: `tanish_admin` (o `tanish_admin@tanish.local`) con `TANISH_ADMIN_PASSWORD`
- Demo: `admin`, `almacen`, `ventas` / `password` (solo entorno local)

## Variables de entorno

```dotenv
WOOCOMMERCE_URL=http://localhost:8080
WOOCOMMERCE_KEY=ck_...
WOOCOMMERCE_SECRET=cs_...
WOOCOMMERCE_WEBHOOK_SECRET=un-secreto-largo
WOOCOMMERCE_VERIFY=false

TANISH_WHATSAPP_NUMBER=51975852932
TANISH_STORE_URL=https://tanish-proxy.solvegrades.workers.dev
TANISH_ADMIN_EMAIL=tanish_admin@tanish.local
TANISH_ADMIN_PASSWORD=...

WHATSAPP_DRIVER=log          # log | webhook | meta
```

## Comandos

| Comando | Función |
|---|---|
| `php artisan woo:sync-products` | Sincroniza el catálogo |
| `php artisan woo:sync-orders --days=30` | Sincroniza pedidos |
| `php artisan woo:sync-customers` | Sincroniza clientes |
| `php artisan woo:reconcile --days=7` | Reconcilia y detecta discrepancias |

El scheduler ejecuta `woo:reconcile` cada hora.

## Webhooks en WooCommerce

En `WooCommerce > Ajustes > Avanzado > Webhooks`:

- URL: `http://laravel/api/webhooks/woocommerce` (red interna) o `https://admin.TUDOMINIO/api/webhooks/woocommerce`
- Secreto: el mismo de `WOOCOMMERCE_WEBHOOK_SECRET`
- Versión: `v3`
- Tópicos: `order.created`, `order.updated`, `order.deleted`, `product.created`, `product.updated`, `product.deleted`, `customer.created`, `customer.updated`

Cada entrega se valida con **HMAC SHA256**, se guarda en `webhook_events` (idempotencia por `payload_hash`) y se procesa en cola.

## Pruebas

```bash
php artisan test
```

Cubren firma, idempotencia, pedidos, inventario, cliente API, notificaciones, GA4, roles, panel y tienda pública.

## Producción

- **Tienda:** `https://tanish-proxy.solvegrades.workers.dev/`
- **Panel Laravel:** `https://tanish-proxy.solvegrades.workers.dev/admin`
- **WordPress admin:** `https://tanish-proxy.solvegrades.workers.dev/wp-admin`

El Worker `tanish-proxy` enruta por ruta: `/admin`, `/livewire*`, `/css*`, `/js*`, `/fonts*`, `/storage*`, `/api*` y `/up` van a Laravel; el resto va a WordPress. `CADDY_ADMIN_DOMAIN` se mantiene como origen interno y `LARAVEL_URL` apunta al dominio público.

```bash
docker compose --env-file .env.production --env-file .env.soa \
  -f docker-compose.prod.yml -f docker-compose.soa.yml up -d --build
```

El overlay agrega `laravel`, `laravel-queue`, `laravel-scheduler`, `redis` y monta `caddy/sites/admin.caddy`. El Caddyfile compartido solo necesita:

```
import /etc/caddy/sites/*.caddy
```

Ver `../docs/arquitectura/IMPLEMENTACION-FASES.md` para el procedimiento completo.

## Reglas de diseño

- Laravel nunca consulta por SQL las tablas de WordPress.
- WooCommerce es la fuente de verdad del stock.
- Los movimientos son inmutables y auditables.
- El checkout real sigue en WooCommerce; Laravel ofrece catálogo y cotización por WhatsApp.
