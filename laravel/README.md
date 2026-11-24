# TANISH — Servicio Laravel (panel + integración)

Segundo servicio de la arquitectura SOA de TANISH. **No reemplaza** a WordPress ni a WooCommerce: los consume por **REST API v3** y **webhooks firmados**.

```
WordPress + WooCommerce  ──webhooks/REST──▶  Laravel + Filament
 (comercio, stock, pedidos)                   (panel, kardex, analítica)
```

## Requisitos

- PHP 8.3+
- Composer 2
- SQLite (desarrollo) o MySQL 8 (producción)
- Redis (producción; opcional en local)

## Puesta en marcha local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Panel: <http://localhost:8000/admin>

Usuarios de demostración creados por el seeder:

| Correo | Contraseña | Rol |
|---|---|---|
| `admin@tanish.test` | `password` | Administrador |
| `almacen@tanish.test` | `password` | Almacén |
| `ventas@tanish.test` | `password` | Ventas |

## Variables de entorno

```dotenv
WOOCOMMERCE_URL=http://localhost:8080
WOOCOMMERCE_KEY=ck_...
WOOCOMMERCE_SECRET=cs_...
WOOCOMMERCE_WEBHOOK_SECRET=un-secreto-largo
WOOCOMMERCE_VERIFY=false      # true en producción con HTTPS
```

Opcionales: `GA4_MEASUREMENT_ID`, `GA4_API_SECRET`, `WHATSAPP_NOTIFY_URL`, `WHATSAPP_NOTIFY_TOKEN`, `WHATSAPP_ADMIN_NUMBER`.

## Comandos

| Comando | Función |
|---|---|
| `php artisan woo:sync-products` | Sincroniza el catálogo como snapshots de lectura |
| `php artisan woo:sync-orders --days=30` | Sincroniza pedidos (0 = todos) |
| `php artisan woo:sync-customers` | Sincroniza clientes |
| `php artisan woo:reconcile --days=7` | Reconcilia todo y detecta discrepancias de stock |

Programación: `woo:reconcile` corre cada hora con el scheduler (`php artisan schedule:work`).

## Webhooks en WooCommerce

En `WooCommerce > Ajustes > Avanzado > Webhooks`, crea un webhook por tópico:

- URL de entrega: `https://admin.TUDOMINIO/api/webhooks/woocommerce`
- Secreto: el mismo valor de `WOOCOMMERCE_WEBHOOK_SECRET`
- Versión de API: `v3`
- Tópicos: `order.created`, `order.updated`, `order.deleted`, `product.created`, `product.updated`, `product.deleted`, `customer.created`, `customer.updated`

Cada entrega se valida con **HMAC SHA256** (`X-WC-Webhook-Signature`), se guarda en `webhook_events` con idempotencia por `payload_hash` y se procesa en cola.

## Pruebas

```bash
php artisan test
```

Cubren: firma inválida/válida, idempotencia, procesamiento de pedidos, movimientos de inventario, paginación del cliente API, roles y acceso al panel.

## Producción (Docker)

Desde la raíz del repositorio:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml -f docker-compose.soa.yml up -d --build
```

El overlay agrega `laravel`, `laravel-queue`, `laravel-scheduler`, `redis` y publica el panel en `CADDY_ADMIN_DOMAIN`. WordPress sigue operando aunque Laravel se detenga.

> Si el volumen de MySQL ya existía, el script `docker/mysql-init-soa.sh` no se ejecuta automáticamente. Crea el esquema y el usuario una sola vez (ver `docs/arquitectura/IMPLEMENTACION-FASES.md`).

## Reglas de diseño

- Laravel **nunca** consulta por SQL las tablas de WordPress.
- WooCommerce es la fuente de verdad del stock.
- Los movimientos de inventario son inmutables y auditables.
- Los webhooks son asíncronos, idempotentes y reconciliables.

## Estado de fases

Ver `../docs/arquitectura/IMPLEMENTACION-FASES.md`.
