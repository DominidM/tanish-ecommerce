# Implementación de la propuesta Laravel — estado por fases

Este documento registra qué se implementó de la [PROPUESTA-LARAVEL.md](./PROPUESTA-LARAVEL.md) y qué queda pendiente.

- **Rama:** `feat/laravel-soa`
- **Aplicación:** `laravel/`
- **Alcance:** Fases 0 a 4 (Fase 5 no comprometida)
- **WordPress + WooCommerce:** sin cambios de código; sigue siendo el núcleo del comercio

---

## Resumen

| Fase | Estado | Entregable principal |
|---|---|---|
| 0 — Preparación y contrato | ✅ Implementada | ADR, overlay Compose, esqueleto Laravel, variables de entorno |
| 1 — Esqueleto y lectura por API | ✅ Implementada | Cliente REST v3, snapshots, comandos de sincronización, panel de lectura |
| 2 — Webhooks, cola y kardex | ✅ Implementada | Endpoint firmado, idempotencia, jobs, kardex de ventas |
| 3 — Panel Filament y roles | ✅ Implementada | Recursos, alta de movimientos, roles admin/almacén/ventas |
| 4 — Analítica y notificaciones | 🟡 Parcial | `purchase` server-side y notificación WhatsApp con log |
| 5 — Front público opcional | ⛔ No iniciada | Fuera del alcance actual |

---

## Fase 0 — Preparación

- [x] Decisión registrada en `ADR-001-soa-laravel.md`.
- [x] Aplicación Laravel 13 en `laravel/`.
- [x] Overlay `docker-compose.soa.yml` sin tocar `docker-compose.prod.yml`.
- [x] `caddy/Caddyfile.soa` con dominio del panel.
- [x] `docker/mysql-init-soa.sh` para el esquema exclusivo de Laravel.
- [x] `.env.soa.example` con las variables nuevas.
- [x] Rollback: detener los servicios Laravel y volver al `Caddyfile` original.

## Fase 1 — Lectura por API

- [x] Cliente `App\Services\WooCommerce\WooCommerceClient` (Basic Auth, timeout, paginación).
- [x] Modelos y tablas: `product_snapshots`, `order_snapshots`, `customer_snapshots`.
- [x] Comandos `woo:sync-products`, `woo:sync-orders`, `woo:sync-customers`.
- [x] Panel Filament de solo lectura para productos, pedidos y clientes.
- [x] Sin endpoints de escritura en esta fase.

## Fase 2 — Webhooks y kardex

- [x] `POST /api/webhooks/woocommerce`.
- [x] Verificación HMAC SHA256 con `X-WC-Webhook-Signature`.
- [x] Idempotencia por `payload_hash` y trazabilidad de `delivery_id`.
- [x] Cola (`database` en local, `redis` en producción) con reintentos y backoff.
- [x] `ProcessWooCommerceWebhook` + manejadores por tópico.
- [x] Movimientos de salida por venta (`source=order`) sin duplicados.
- [x] Reversión al cancelar (`source=reversal`).
- [x] Comando de reconciliación horaria.

## Fase 3 — Panel y roles

- [x] Recursos Filament: productos, pedidos, clientes, kardex, webhooks.
- [x] Alta de entradas/salidas/ajustes vía `InventoryService` (lock + transacción + API).
- [x] Validación de stock insuficiente antes y después de la API.
- [x] Roles `admin`, `almacen`, `ventas` y acceso al panel.
- [x] Almacén puede crear movimientos; ventas no; solo admin elimina.

## Fase 4 — Analítica y notificaciones

- [x] Evento `purchase` server-side hacia GA4 Measurement Protocol.
- [x] Deduplicación por `event_id` (`purchase:{order_id}`).
- [x] Registro de notificaciones y envío por webhook configurable.
- [ ] Tablero dedicado de analítica y notificaciones en el panel.
- [ ] Integración con el proveedor real de WhatsApp (hoy es un endpoint genérico).

## Fase 5 — Front público

No iniciada. La tienda pública sigue en WordPress, tal como exige el requisito académico.

---

## Cómo demostrarlo

1. `php artisan migrate --seed` y `php artisan serve`.
2. Entrar a `/admin` con `admin@tanish.test` / `password`.
3. Configurar el webhook en WooCommerce con el secreto de `.env`.
4. Crear un pedido de prueba y ver:
   - `Pedidos` con el snapshot y `stock_recordado`.
   - `Kardex` con el movimiento `Venta`.
   - `Webhooks` con el evento `processed`.
5. Registrar una entrada manual en `Kardex > Crear` y verificar el nuevo stock en WooCommerce.
6. Intentar una salida mayor al stock y comprobar el rechazo.

## Evidencias sugeridas

- Captura de `/admin` con los KPIs.
- Captura de `webhook_events` con estado `processed`.
- Captura del kardex con un movimiento `Venta`.
- Captura de stock antes/después en WooCommerce.
- Salida de `php artisan test`.

## Limitaciones conocidas

- **Horizon** no se instaló porque requiere `ext-pcntl`, ausente en Windows. En Linux/Docker se puede agregar sin cambiar el código; las colas funcionan con `queue:work`.
- El esquema `tanish_laravel` no se crea si el volumen MySQL ya existía; ejecutar el script de inicialización una vez.
- El envío de WhatsApp es un contrato genérico (`POST` a `WHATSAPP_NOTIFY_URL`), no un proveedor específico.
- No hay front público en Laravel: esa decisión queda para una fase posterior.

## Variables y secretos

- `WOOCOMMERCE_KEY` / `WOOCOMMERCE_SECRET`: credenciales REST v3 (una de solo lectura y otra de escritura acotada, idealmente).
- `WOOCOMMERCE_WEBHOOK_SECRET`: mismo valor configurado en WooCommerce.
- `LARAVEL_APP_KEY`: generar con `php artisan key:generate --show`.
- `LARAVEL_DB_*`: usuario exclusivo del esquema `tanish_laravel`.
