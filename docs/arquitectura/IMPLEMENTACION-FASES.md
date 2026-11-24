# Implementación de la propuesta Laravel — estado por fases

Este documento registra qué se implementó de la [PROPUESTA-LARAVEL.md](./PROPUESTA-LARAVEL.md) y cómo operarlo.

- **Rama de implementación:** `feat/laravel-soa`
- **Aplicación:** `laravel/`
- **WordPress + WooCommerce:** sin cambios de código; sigue siendo el núcleo del comercio

---

## Resumen

| Fase | Estado | Entregable principal |
|---|---|---|
| 0 — Preparación y contrato | ✅ | ADR, overlay Compose, esqueleto Laravel, variables de entorno |
| 1 — Lectura por API | ✅ | Cliente REST v3, snapshots, comandos de sincronización, panel de lectura |
| 2 — Webhooks y kardex | ✅ | Endpoint firmado, idempotencia, jobs, kardex de ventas |
| 3 — Panel y roles | ✅ | Recursos Filament, alta de movimientos, roles admin/almacén/ventas |
| 4 — Analítica y notificaciones | ✅ | Panel de eventos, GA4 server-side, WhatsApp (webhook/Meta), reenvíos |
| 5 — Front público | ✅ MVP | Home, catálogo, producto y cotización por WhatsApp |

---

## Fase 0 — Preparación

- [x] Decisión en `ADR-001-soa-laravel.md`.
- [x] Aplicación Laravel 13 en `laravel/`.
- [x] Overlay `docker-compose.soa.yml`.
- [x] Snippet `caddy/sites/admin.caddy` (no reemplaza el Caddyfile compartido).
- [x] `docker/mysql-init-soa.sh` para el esquema exclusivo de Laravel.
- [x] `.env.soa.example` con las variables nuevas.
- [x] Rollback: detener los servicios Laravel y retirar el snippet de Caddy.

## Fase 1 — Lectura por API

- [x] `WooCommerceClient` (Basic Auth, timeout, paginación).
- [x] Tablas y modelos: `product_snapshots`, `order_snapshots`, `customer_snapshots`.
- [x] Comandos `woo:sync-products`, `woo:sync-orders`, `woo:sync-customers`.
- [x] Panel Filament de solo lectura.
- [x] Sin escritura en esta fase.

## Fase 2 — Webhooks y kardex

- [x] `POST /api/webhooks/woocommerce`.
- [x] Firma HMAC SHA256 (`X-WC-Webhook-Signature`).
- [x] Idempotencia por `payload_hash` y trazabilidad con `delivery_id`.
- [x] Cola (`database` local, `redis` producción) con reintentos y backoff.
- [x] Movimientos de salida por venta (`source=order`) y reversión al cancelar.
- [x] Reconciliación horaria (`woo:reconcile`).

## Fase 3 — Panel y roles

- [x] Recursos: productos, pedidos, clientes, kardex, webhooks.
- [x] Alta de entradas/salidas/ajustes vía `InventoryService` (lock + transacción + API).
- [x] Validación de stock insuficiente.
- [x] Roles `admin`, `almacen`, `ventas`.
- [x] Almacén crea movimientos; ventas no; solo admin elimina.

## Fase 4 — Analítica y notificaciones

- [x] Recurso **Analítica** con filtros y reenvío a GA4.
- [x] Recurso **Notificaciones** con filtros y reenvío.
- [x] `purchase` server-side a GA4 (Measurement Protocol) con deduplicación.
- [x] WhatsApp con drivers `webhook` y `meta` (Cloud API) y modo `log`/skip.
- [x] Widgets: ingresos del mes, ticket promedio, eventos y notificaciones.
- [x] Gráfico de ingresos y tabla de productos más vendidos.

## Fase 5 — Front público (MVP)

- [x] `GET /` — home con destacados y categorías.
- [x] `GET /tienda` — catálogo con búsqueda y filtro por categoría.
- [x] `GET /producto/{slug|id}` — ficha de producto.
- [x] Cotización en sesión (`/cotizacion`) y envío por WhatsApp.
- [x] SEO básico (title, description, canonical, Open Graph) y CSS propio (sin build de Node).
- [x] Seeder `DemoCatalogSeeder` para probar el front sin WooCommerce.

> **Decisión de diseño:** el carrito y el checkout reales siguen en WooCommerce. Laravel ofrece catálogo y **cotización por WhatsApp**, que es el canal comercial de TANISH. Reconstruir el checkout en Laravel queda para una fase posterior.

---

## Uso local

```bash
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=DemoCatalogSeeder   # catálogo de demostración
php artisan serve
```

- Tienda: <http://localhost:8000/>
- Panel: <http://localhost:8000/admin>
- Usuario local: `tanish_admin@tanish.local` / valor de `TANISH_ADMIN_PASSWORD`
- Usuarios demo: `admin@tanish.test`, `almacen@tanish.test`, `ventas@tanish.test` / `password` (solo local)

---

## Producción

### 1. Variables

Crea `.env.soa` en el VPS a partir de `.env.soa.example`:

```dotenv
CADDY_ADMIN_DOMAIN=http://admin.185.176.254.183.nip.io
LARAVEL_URL=https://tanish-proxy.solvegrades.workers.dev
LARAVEL_APP_KEY=base64:...
LARAVEL_DB_DATABASE=tanish_laravel
LARAVEL_DB_USERNAME=tanish_laravel
LARAVEL_DB_PASSWORD=...
WOOCOMMERCE_URL=http://wordpress:80
WOOCOMMERCE_KEY=ck_...
WOOCOMMERCE_SECRET=cs_...
WOOCOMMERCE_WEBHOOK_SECRET=...
TANISH_STORE_URL=https://tanish-proxy.solvegrades.workers.dev
TANISH_ADMIN_EMAIL=tanish_admin@tanish.local
TANISH_ADMIN_PASSWORD=...
WHATSAPP_DRIVER=log
```

`LARAVEL_APP_KEY` se genera con `php artisan key:generate --show`.

La URL pública unificada es:

- Tienda: `https://tanish-proxy.solvegrades.workers.dev/`
- Panel Laravel: `https://tanish-proxy.solvegrades.workers.dev/admin`
- WordPress admin: `https://tanish-proxy.solvegrades.workers.dev/wp-admin`
- API/webhooks Laravel: `https://tanish-proxy.solvegrades.workers.dev/api/webhooks/woocommerce`

El Worker `tanish-proxy` enruta por ruta: `/admin`, `/livewire*`, `/css*`, `/js*`, `/fonts*`, `/storage*`, `/api*` y `/up` van a Laravel; el resto va a WordPress.

### 2. Esquema MySQL

Si el volumen de MySQL ya existía, el script de inicialización no corre. Ejecuta una vez:

```bash
docker exec -i tanish-prod-mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<'SQL'
CREATE DATABASE IF NOT EXISTS tanish_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'tanish_laravel'@'%' IDENTIFIED BY 'TU_PASSWORD';
GRANT ALL PRIVILEGES ON tanish_laravel.* TO 'tanish_laravel'@'%';
FLUSH PRIVILEGES;
SQL
```

### 3. Caddy compartido

El VPS comparte Caddy con otros proyectos. **No reemplaces el Caddyfile**; agrega una sola línea al inicio:

```
import /etc/caddy/sites/*.caddy
```

El overlay monta `./caddy/sites` y el snippet `admin.caddy` publica el panel. Reinicia Caddy:

```bash
docker restart tanish-prod-caddy
```

### 4. Desplegar

```bash
docker compose --env-file .env.production --env-file .env.soa \
  -f docker-compose.prod.yml -f docker-compose.soa.yml up -d --build

docker compose --env-file .env.production --env-file .env.soa \
  -f docker-compose.prod.yml -f docker-compose.soa.yml \
  exec -T laravel php artisan migrate --force

docker compose --env-file .env.production --env-file .env.soa \
  -f docker-compose.prod.yml -f docker-compose.soa.yml \
  exec -T laravel php artisan db:seed --force
```

El script `scripts/deploy-production.sh` detecta `.env.soa` y hace todo lo anterior automáticamente.

### 5. Sincronizar y webhook

```bash
# Productos reales de WooCommerce
docker exec tanish-laravel php artisan woo:sync-products

# Webhook interno (WordPress -> Laravel), creado con la REST API de WooCommerce
curl -u "ck_...:cs_..." -X POST http://wordpress/wp-json/wc/v3/webhooks \
  -H 'Content-Type: application/json' \
  -d '{"name":"Laravel SOA","topic":"order.updated","delivery_url":"http://laravel/api/webhooks/woocommerce","secret":"TU_SECRETO","status":"active"}'
```

Repite el webhook para los tópicos necesarios (`order.created`, `product.updated`, `customer.updated`, etc.).

### 6. Verificación

```bash
curl -fsS https://tanish-proxy.solvegrades.workers.dev/up          # health
curl -fsS https://tanish-proxy.solvegrades.workers.dev/ | head     # tienda
curl -fsS https://tanish-proxy.solvegrades.workers.dev/admin/login # panel
docker ps --filter name=tanish
```

---

## Evidencias sugeridas

- Captura de `/admin` con los KPIs y gráficos.
- Captura de `Analítica` con un `purchase` en estado `sent` o `skipped`.
- Captura de `Notificaciones` con su estado.
- Captura del kardex con un movimiento `Venta`.
- Captura de la tienda pública en Laravel.
- Salida de `php artisan test` (29 pruebas).

## Limitaciones conocidas

- **Horizon** no se instaló (requiere `ext-pcntl`, ausente en Windows). En Linux se puede agregar sin cambiar código.
- El esquema `tanish_laravel` debe crearse una vez si el volumen MySQL ya existía.
- El front público es un MVP de catálogo + cotización; **no** reemplaza el checkout de WooCommerce.
- El dominio del panel usa `nip.io` sobre HTTP hasta que exista un dominio propio con DNS/TLS.
- El envío de WhatsApp requiere configurar `WHATSAPP_DRIVER` y sus credenciales; por defecto queda en `log`.
