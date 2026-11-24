# Propuesta de evolución arquitectónica: SOA de dos servicios con Laravel

| Campo | Valor |
|---|---|
| Documento | Propuesta de arquitectura objetivo |
| Proyecto | TANISH E-Commerce |
| Rama | `docs/arquitectura-laravel` |
| Estado | Propuesta, no implementada |
| Alcance | Documental. No modifica código, contenedores ni base de datos |
| Documento base | `docs/arquitectura/ARQUITECTURA-ACTUAL.md` |
| Decisión registrada | `docs/arquitectura/ADR-001-soa-laravel.md` |
| Implementación | `docs/arquitectura/IMPLEMENTACION-FASES.md` |
| Relacionados | `docs/sprints/TRACEABILITY.md`, `docs/DEPLOYMENT.md`, `docker-compose.prod.yml` |

---

## 1. Objetivo y restricción académica

### 1.1 Objetivo

Definir una evolución arquitectónica para TANISH que permita incorporar Laravel sin renunciar a WordPress + WooCommerce, manteniendo el flujo comercial exigido por el documento académico y agregando capacidades de administración, inventario, analítica y notificaciones.

La propuesta responde a una tensión concreta:

- El profesor exige WordPress con WooCommerce como plataforma del proyecto.
- El alumno quiere incorporar Laravel como tecnología de desarrollo.
- El sistema actual ya funciona sobre WordPress + WooCommerce y tiene evidencia académica acumulada.

La salida no es reemplazar una plataforma por otra. La salida es **sumar un segundo servicio** con responsabilidades acotadas y un contrato de integración explícito.

### 1.2 Restricción académica que se conserva

**WordPress + WooCommerce no se elimina, no se reemplaza y no se degrada a un componente opcional.** WooCommerce sigue siendo el núcleo transaccional del proyecto:

| Elemento exigido | Dónde permanece | Estado |
|---|---|---|
| Catálogo, categorías y precios | WordPress + WooCommerce | Se mantiene |
| Carrito y checkout | WooCommerce | Se mantiene |
| Formulario de checkout | WooCommerce | Se mantiene |
| Creación de pedidos | WooCommerce | Se mantiene |
| Descuento de stock por pedido | WooCommerce | Se mantiene |
| Pasarelas Yape y Mercado Pago | Plugin TANISH sobre WooCommerce | Se mantienen |
| Canal WhatsApp de venta | Tema + plugin TANISH | Se mantiene |
| Evidencia de Sprint 00 a Sprint 06 | Repositorio y base de datos | No se altera |

La incorporación de Laravel ocurre en la capa de **integración y administración**, no en la capa de comercio. Laravel no reemplaza el checkout, no emite pedidos por su cuenta y no se convierte en la fuente de verdad del stock.

### 1.3 Qué no cambia

- Los objetivos académicos OE1 a OE5 siguen siendo válidos.
- Los sprints 00 a 06 y sus evidencias siguen siendo válidos.
- La matriz `docs/sprints/TRACEABILITY.md` no se reescribe: se actualiza cuando cada fase se ejecute.
- El pedido de prueba Order ID 60, el stock 30 → 29 y las capturas de Sprint 01 siguen siendo evidencia válida.
- El flujo B2C `catálogo → producto → carrito → checkout → pedido` sigue ocurriendo en WooCommerce.
- El flujo alternativo por WhatsApp sigue disponible en el frontend.

### 1.4 Qué agrega Laravel

- Un panel administrativo moderno con Filament.
- Un kardex de inventario con trazabilidad ampliada.
- Notificaciones transaccionales por WhatsApp.
- Registro y auditoría de eventos de analítica, además del envío server-side de `purchase`.
- Recepción y administración de formularios.
- Reportes operativos y alertas de reconciliación.
- Una cola de trabajo con reintentos, idempotencia y trazabilidad de webhooks.

### 1.5 Principio rector

> WooCommerce decide y ejecuta el comercio. Laravel observa, administra, complementa y audita. Cuando Laravel necesita cambiar stock, lo hace a través de la API de WooCommerce, nunca escribiendo directamente en las tablas de WordPress.

Este principio se denomina en el documento **regla de soberanía de WooCommerce**.

---

## 2. Decisión arquitectónica: de monolito a SOA de dos servicios

### 2.1 Punto de partida

El sistema actual es un monolito modular:

- WordPress resuelve el CMS, la autenticación, el tema y el frontend.
- WooCommerce resuelve catálogo, carrito, checkout, pedidos, stock y pagos.
- El plugin `tanish-inventory` agrega inventario, kardex, WhatsApp y pasarelas simuladas.
- Los mu-plugins agregan cron de desarrollo y registro de formularios.
- Todo comparte una base de datos MySQL y un contenedor WordPress.

Ese monolito no es un problema para el alcance académico. Es una base estable y evidenciada. El problema aparece cuando se quiere incorporar Laravel: si se introduce Laravel dentro del mismo proceso de WordPress, se pierde la separación tecnológica; si se reemplaza WooCommerce, se incumple el requisito.

### 2.2 Decisión

Se adopta una **arquitectura orientada a servicios (SOA) de dos servicios**:

1. **Servicio de comercio**: WordPress + WooCommerce + plugin TANISH. Conserva la responsabilidad transaccional.
2. **Servicio de integración y administración**: Laravel + Filament. Consume eventos y datos del servicio de comercio, mantiene su propia base de datos y expone el panel administrativo.

La integración se realiza con dos mecanismos nativos de WooCommerce:

- **Webhooks nativos** para eventos asíncronos.
- **WooCommerce REST API v3** para lectura de datos y para la actualización controlada de stock.

No se usan plugins de terceros para la integración. No se requiere un bus de eventos externo. No se comparte la base de datos entre servicios.

### 2.3 Por qué SOA y no microservicios estrictos

| Criterio | Monolito actual | SOA propuesta | Microservicios estrictos |
|---|---|---|---|
| Número de servicios | 1 | 2 | Muchos, por dominio |
| Granularidad | Alta cohesión interna | Servicios gruesos | Servicios finos |
| Base de datos | Una | Una por servicio, esquemas separados | Una por servicio |
| Comunicación | Llamadas internas | HTTP/JSON + eventos | HTTP/gRPC + eventos |
| Despliegue | Un artefacto | Dos artefactos independientes | Muchos artefactos |
| Complejidad operativa | Baja | Media | Alta |
| Transacciones | Locales | Sin transacciones distribuidas; compensación y reconciliación | Sagas y consistencia eventual |
| Encaje con el proyecto | Base actual | Adecuado | Sobredimensionado |

La propuesta es SOA porque:

- Los servicios son **gruesos y con responsabilidades claras**.
- El contrato es explícito y versionado.
- Los servicios son **heterogéneos**: WordPress/PHP y Laravel/PHP.
- Se reutiliza la plataforma existente en lugar de reconstruirla.
- La integración es **débilmente acoplada**: Laravel no conoce las tablas de WordPress.
- No se necesita descubrimiento de servicios, malla de servicios ni orquestación de contenedores.

No es microservicios estrictos porque:

- Solo hay dos servicios.
- WordPress sigue siendo un monolito y no se va a descomponer.
- La infraestructura es compartida: un VPS, una red Docker, una instancia MySQL con dos esquemas.
- No hay equipos independientes ni despliegues por dominio.
- Introducir microservicios reales agregaría complejidad operativa sin valor académico proporcional.

### 2.4 Patrón de migración

Se aplica una variante del patrón **Strangler Fig**, pero con un límite explícito:

- Se extraen capacidades **administrativas y de integración**: kardex, panel, notificaciones, analítica, formularios, reportes.
- **No se estrangula el comercio**: catálogo, carrito, checkout, pedidos, stock y pagos permanecen en WooCommerce.

La metáfora correcta no es "reemplazar el monolito", sino **"agregar un satélite administrativo al núcleo comercial"**.

### 2.5 Reglas de diseño obligatorias

1. Laravel no ejecuta SQL contra tablas de WordPress.
2. Laravel no escribe precios, nombres ni estados de pedido por API; solo stock, y solo a través de un servicio de inventario acotado.
3. WooCommerce sigue descontando stock al confirmar pedidos.
4. Los webhooks son la vía principal de sincronización; la API es la vía de reconciliación y de escritura controlada.
5. Toda escritura de inventario desde Laravel queda registrada en el kardex con usuario, motivo, stock anterior y stock posterior.
6. Si Laravel está caído, la tienda y el checkout siguen funcionando.
7. Si un webhook falla, WooCommerce no se bloquea; Laravel reintenta y reconcilia.
8. Ningún servicio accede a la base de datos del otro.
9. Los secretos no se versionan.
10. La documentación académica existente no se invalida.

---

## 3. Diagrama de arquitectura propuesta

### 3.1 Vista general

```text
                              Internet
                                 |
                     Cloudflare (DNS + TLS)
                                 |
                          Caddy :443
                         /            \
             tanish.dominio        admin.dominio
                    |                    |
        +------------------------+   +---------------------------+
        |  SERVICIO DE COMERCIO  |   | SERVICIO DE INTEGRACION   |
        |  WordPress + WooCommerce|  | Laravel + Filament        |
        |                        |   |                           |
        |  - Catalogo            |   |  - Panel administrativo   |
        |  - Categorias          |   |  - Kardex                 |
        |  - Carrito             |   |  - Inventario manual      |
        |  - Checkout            |   |  - Notificaciones WhatsApp|
        |  - Pedidos             |   |  - Analitica y reportes   |
        |  - Stock               |   |  - Formularios            |
        |  - Pagos Yape/MP       |   |  - Webhook events         |
        |  - WhatsApp venta      |   |  - Reconciliacion         |
        +-----------+------------+   +-------------+-------------+
                    |                              |
                    |  Webhooks nativos (HTTPS)    |
                    +----------------------------->|
                    |  order.created               |
                    |  order.updated               |
                    |  product.updated             |
                    |  customer.created            |
                    |                              |
                    |  REST API v3 (HTTPS)         |
                    |<-----------------------------+
                    |  GET productos/pedidos       |
                    |  PUT stock_quantity          |
                    |                              |
                    |                              |
        +-----------+------------+   +-------------+-------------+
        |  MySQL 8               |   |  Redis                    |
        |  tanish_wordpress      |   |  colas, cache, sesiones   |
        |  (solo WordPress)      |   |  (solo Laravel)           |
        |                        |   |                           |
        |  tanish_laravel        |   |  Horizon                  |
        |  (solo Laravel)        |   |  queue workers            |
        +------------------------+   +---------------------------+
```

### 3.2 Flujo de un pedido

```text
Cliente
  |
  | 1. Checkout en WooCommerce
  v
WordPress + WooCommerce
  |
  | 2. Crea pedido, descuenta stock, registra pago
  | 3. Dispara webhook order.created
  v
Laravel: POST /api/webhooks/woocommerce
  |
  | 4. Verifica X-WC-Webhook-Signature (HMAC SHA256)
  | 5. Guarda X-WC-Webhook-Delivery-ID y el hash del payload en webhook_events
  | 6. Responde 200 en menos de 2 segundos
  v
Redis + Horizon
  |
  | 7. Job ProcessWooCommerceWebhook
  v
Laravel Worker
  |
  | 8. GET /wp-json/wc/v3/orders/{id}  (detalle actualizado)
  | 9. Upsert de order_snapshot
  | 10. Confirma el descuento de stock y registra inventory_movements por cada linea
  | 11. Encola notificacion WhatsApp
  | 12. Envia purchase a GA4 (si el pedido esta pagado)
  v
Laravel DB: webhook_events, order_snapshots, inventory_movements, notification_logs
```

### 3.3 Flujo de un movimiento manual de inventario

```text
Usuario de almacen
  |
  | 1. Formulario Filament: entrada / salida / ajuste
  v
Laravel
  |
  | 2. Valida motivo, cantidad y permisos
  | 3. Bloquea el producto (lock Redis stock:product:{id})
  | 4. GET /wp-json/wc/v3/products/{id}  -> stock_before
  | 5. Calcula stock_after y valida salida <= stock
  | 6. PUT /wp-json/wc/v3/products/{id}  -> stock_quantity
  | 7. GET /wp-json/wc/v3/products/{id}  -> verifica stock_after
  | 8. Inserta inventory_movements con stock_before y stock_after
  | 9. Libera el lock
  v
WooCommerce conserva la cantidad de stock.
Laravel conserva el historial del movimiento.
```

### 3.4 Topología de despliegue

```text
VPS Ubuntu 24.04
|
+-- Docker network: tanish-network
    |
    +-- mysql:3306            (sin puerto publicado)
    |     +-- tanish_wordpress
    |     +-- tanish_laravel
    |
    +-- wordpress:80          (sin puerto publicado)
    |
    +-- laravel:8000          (sin puerto publicado)
    |
    +-- laravel-queue         (sin puerto)
    |
    +-- laravel-scheduler     (sin puerto)
    |
    +-- redis:6379            (sin puerto publicado)
    |
    +-- caddy:80,443          (unico servicio publicado)
          +-- tanish.dominio  -> wordpress:80
          +-- admin.dominio   -> laravel:8000
```

---

## 4. Responsabilidades por servicio

### 4.1 Matriz de responsabilidades

| Capacidad | WordPress + WooCommerce | Laravel + Filament | Fuente de verdad |
|---|---|---|---|
| Catálogo y categorías | Publica y administra | Lee y muestra en panel | WooCommerce |
| Precios | Define y aplica | Solo lectura | WooCommerce |
| Carrito | Gestiona | No participa | WooCommerce |
| Checkout | Gestiona formulario y validaciones | No participa | WooCommerce |
| Pedidos | Crea, actualiza y conserva | Replica y muestra | WooCommerce |
| Stock | Calcula, descuenta y conserva | Lee, audita y solicita cambios | WooCommerce |
| Pagos Yape / Mercado Pago | Procesa el flujo de pago | No procesa pagos | WooCommerce |
| Clientes | Registra y autentica | Replica para notificaciones | WooCommerce |
| WhatsApp de venta | Botón click-to-chat en frontend | Notificaciones transaccionales | Ambos, con propósitos distintos |
| Kardex | Historial legado en `wp_tanish_inventory_movements` | Historial nuevo y consolidado | Laravel para movimientos; WooCommerce para cantidad |
| Inventario manual | Plugin legado, pasa a lectura | Formularios de entrada, salida y ajuste | Laravel como interfaz; WooCommerce como stock |
| Analítica GA4 | Eventos del embudo en frontend | `purchase` server-side y auditoría | GA4 para métricas; Laravel para log |
| Formularios | Captura actual en CPT | Administración y reportes | Laravel para nuevos; WordPress para legado |
| Usuarios y roles | Administra acceso a WordPress | Administra acceso al panel | Cada servicio |
| Webhooks | Emite eventos | Recibe, valida, persiste y procesa | Laravel para trazabilidad de entrega |
| Reconciliación | No la necesita | Compara y alerta diferencias | Laravel |

### 4.2 Propiedad de datos

| Dato | Servicio propietario | Acceso del otro servicio |
|---|---|---|
| Productos | WooCommerce | REST API de solo lectura |
| Pedidos | WooCommerce | REST API de solo lectura |
| Stock | WooCommerce | REST API de lectura y escritura acotada |
| Clientes | WooCommerce | REST API de solo lectura |
| Pagos | WooCommerce | Webhooks y lectura de pedido |
| Movimientos de inventario | Laravel | Ninguno |
| Snapshot de productos y pedidos | Laravel | Ninguno |
| Eventos de webhook | Laravel | Ninguno |
| Notificaciones | Laravel | Ninguno |
| Eventos de analítica | Laravel | Ninguno |
| Formularios nuevos | Laravel | Ninguno |
| Usuarios del panel | Laravel | Ninguno |

**Regla:** no hay claves foráneas entre bases de datos. Las referencias son identificadores de negocio, por ejemplo `wc_order_id` o `wc_product_id`, sin integridad referencial cruzada.

### 4.3 Delimitación de la base de datos

Se recomienda una instancia MySQL 8 compartida con dos esquemas separados por razones operativas del VPS:

- `tanish_wordpress`: propiedad exclusiva de WordPress.
- `tanish_laravel`: propiedad exclusiva de Laravel.

El usuario MySQL de Laravel solo tiene privilegios sobre `tanish_laravel`. No puede leer ni escribir `tanish_wordpress`. Si en el futuro se separan los servidores, el contrato no cambia: Laravel ya no depende de SQL cruzado.

Esta decisión evita el anti-patrón de monolito distribuido con base de datos compartida. La instancia es compartida; los esquemas y permisos no.

---

## 5. Contrato de integración

### 5.1 Principios del contrato

1. **WooCommerce es el publicador.** Laravel es el consumidor.
2. **Los webhooks son asíncronos y no confiables.** Pueden llegar duplicados, desordenados o no llegar.
3. **La API es la fuente de reconciliación.** No se asume que el webhook sea suficiente.
4. **Toda entrega se verifica criptográficamente.**
5. **Toda entrega se registra antes de procesarse.**
6. **El procesamiento es idempotente.**
7. **El contrato se versiona.** Se usa WooCommerce REST API v3 y webhooks nativos.
8. **Los payloads no se transforman antes de verificar la firma.**

### 5.2 Webhooks nativos

Configuración: `WooCommerce > Ajustes > Avanzado > Webhooks > Añadir webhook`.
Cada webhook se configura con:

- Tema o evento.
- URL de entrega.
- Secreto.
- Versión de API: v3.
- Estado: activo.

| Evento | Cuándo dispara | Qué hace Laravel |
|---|---|---|
| `order.created` | WooCommerce crea un pedido, desde checkout, admin o API | Verifica firma, guarda el evento y encola el job. El job hace upsert de `order_snapshots`, determina si WooCommerce ya descontó stock y, si corresponde, registra los movimientos de salida. Encola la notificación al administrador. |
| `order.updated` | Cambia el estado, el pago, la dirección, el stock o los metadatos del pedido | Actualiza el snapshot. Si el estado pasa a `processing` o `completed`, confirma el descuento de stock en productos que lo gestionan, registra el pago y envía `purchase` a GA4 una sola vez. Si el estado pasa a `cancelled` o `refunded`, registra la reversión solo si WooCommerce restauró el stock; si no puede determinarlo, alerta. |
| `product.created` | Se crea un producto | Crea el snapshot del producto y su stock inicial. |
| `product.updated` | Cambia el producto, el precio o el stock | Actualiza el snapshot. Si el cambio de stock no se puede atribuir a un pedido o a un movimiento manual, marca la diferencia para revisión. |
| `product.deleted` | Se elimina un producto | Marca el snapshot como eliminado; no borra el kardex histórico. |
| `customer.created` | Se registra un cliente | Crea el snapshot del cliente y su preferencia de notificación. |
| `customer.updated` | Cambia el cliente | Actualiza el snapshot y respeta la baja de consentimiento. |
| `order.deleted` | Se elimina un pedido | Marca el snapshot como eliminado y conserva la auditoría. Evento opcional. |
| `coupon.created`, `coupon.updated`, `coupon.deleted` | Cambios de cupones | Solo si se habilitan reportes de promociones. Evento opcional. |

Notas de implementación:

- El payload de un webhook de pedido es el objeto de pedido de la API v3. No se debe consultar `wp_posts` ni `wp_postmeta`, porque el proyecto usa HPOS (`woocommerce_custom_orders_table_enabled=yes`).
- `order.created` no implica pago ni implica, por sí solo, que el stock ya fue descontado. El evento `purchase` se envía cuando el pedido alcanza un estado pagado.
- Para pedidos, Laravel no asume el descuento de stock: lo confirma con el estado del pedido y con el snapshot del producto. Si no puede atribuir el cambio, deja el movimiento en `pending_review` y lo reporta en la reconciliación.
- Los webhooks de WooCommerce se ejecutan después de la operación. No son transaccionales respecto del checkout.
- Si un webhook no llega, el job de reconciliación corrige el snapshot y registra la incidencia.

### 5.3 REST API v3

Autenticación: clave y secreto de consumidor de WooCommerce sobre HTTPS. Se recomienda usar dos pares de claves:

- **Clave de solo lectura** para catálogo, pedidos y clientes.
- **Clave de lectura y escritura** para el servicio de inventario, usada únicamente para actualizar stock.

| Método y endpoint | Uso | Frecuencia | Escritura |
|---|---|---|---|
| `GET /wp-json/wc/v3/products` | Sincronización inicial y reconciliación de catálogo | Manual, nocturna y bajo demanda | No |
| `GET /wp-json/wc/v3/products/{id}` | Leer stock y datos antes de un movimiento | Por movimiento | No |
| `PUT /wp-json/wc/v3/products/{id}` | Actualizar `stock_quantity` y `manage_stock` | Solo desde el servicio de inventario | Sí, acotada |
| `GET /wp-json/wc/v3/products/{id}/variations` | Variantes, si el catálogo las incorpora | Bajo demanda | No |
| `GET /wp-json/wc/v3/orders` | Reconciliación de pedidos y recuperación de eventos perdidos | Cada 15 minutos y nocturna | No |
| `GET /wp-json/wc/v3/orders/{id}` | Detalle actualizado para procesar un webhook | Por pedido | No |
| `GET /wp-json/wc/v3/customers` | Sincronización de clientes | Nocturna | No |
| `GET /wp-json/wc/v3/products/categories` | Sincronización de categorías | Nocturna | No |
| `GET /wp-json/wc/v3/webhooks` | Auditoría de webhooks configurados | Bajo demanda | No |
| `GET /wp-json/wc/v3/system_status` | Diagnóstico autenticado | Bajo demanda | No |
| `GET /wp-json/` | Verificación de disponibilidad del sitio | Healthcheck | No |

Ejemplo de actualización de stock:

```http
PUT /wp-json/wc/v3/products/21
Authorization: Basic <base64(consumer_key:consumer_secret)>
Content-Type: application/json
```

```json
{
  "manage_stock": true,
  "stock_quantity": 29
}
```

Reglas de uso:

- Paginación con `per_page=100` y `page=N`.
- Reintentos con backoff ante `429`, `500`, `502`, `503` o timeout.
- Límite de concurrencia bajo para no saturar WooCommerce.
- No se actualizan precios, nombres, descripciones ni estados de pedido desde Laravel.
- Para productos variables, el stock se actualiza en la variación correspondiente, no en el producto padre.
- La API se consume desde la red interna (`http://wordpress:80`) cuando Laravel y WordPress comparten red Docker; si se separan, se usa HTTPS público.

### 5.4 Seguridad del webhook

WooCommerce firma el cuerpo crudo de la petición con HMAC SHA256 y lo envía en el header `X-WC-Webhook-Signature` en Base64.

Fórmula:

```text
firma_esperada = base64( HMAC-SHA256( cuerpo_crudo, secreto_compartido ) )
```

Validación obligatoria:

```php
$raw = $request->getContent();
$expected = base64_encode(hash_hmac('sha256', $raw, $secret, true));

if (! hash_equals($expected, (string) $request->header('X-WC-Webhook-Signature'))) {
    abort(401, 'Firma invalida');
}
```

Controles:

| Control | Implementación |
|---|---|
| Secreto compartido | `WC_WEBHOOK_SECRET` en Laravel y en la configuración del webhook de WooCommerce |
| Secreto por webhook | Para el MVP se usa un secreto por entorno. Si se configuran secretos distintos, el controlador resuelve el secreto por `X-WC-Webhook-ID`. |
| Algoritmo | HMAC SHA256 |
| Codificación | Base64, comparación con `hash_equals` |
| Cuerpo verificado | Bytes crudos, antes de decodificar JSON |
| HTTPS | Obligatorio si el webhook cruza hosts; preferible ruta interna en la red Docker |
| Secreto por entorno | Desarrollo, staging y producción usan secretos distintos |
| Rotación | Se acepta el secreto anterior durante una ventana de 24 horas y luego se elimina |
| Replay | `payload_hash` único y `X-WC-Webhook-Delivery-ID` conservado para auditoría |
| Tamaño | Límite de 1 MB por payload |
| Validación | `Content-Type: application/json`, JSON válido y campos mínimos |
| Respuesta inválida | `401` firma inválida, `422` cabeceras o payload inválidos, `503` si no se pudo persistir |

Cabeceras que se registran:

- `X-WC-Webhook-Delivery-ID`
- `X-WC-Webhook-Topic`
- `X-WC-Webhook-Resource`
- `X-WC-Webhook-Event`
- `X-WC-Webhook-Source`
- `X-WC-Webhook-Signature` (no se almacena completa; solo un hash o indicador de validación)

La URL de entrega recomendada es interna:

```text
http://laravel:8000/api/webhooks/woocommerce
```

Ventajas: no expone el endpoint a Internet, evita tráfico innecesario por Caddy y reduce la superficie de ataque. La firma HMAC se sigue validando. Si en el futuro WordPress y Laravel se despliegan en hosts distintos, se usa:

```text
https://admin.dominio.com/api/webhooks/woocommerce
```

con TLS, límite de tasa y allowlist de origen.

### 5.5 Idempotencia

El objetivo no es "exactamente una vez", que no es alcanzable en un sistema distribuido, sino **efectivamente una vez**.

#### Tabla `webhook_events`

```text
webhook_events
├── id                  BIGINT UNSIGNED PK
├── delivery_id         VARCHAR(191) INDEX NOT NULL
├── payload_hash        CHAR(64) UNIQUE NOT NULL
├── topic               VARCHAR(100) INDEX
├── resource            VARCHAR(50) NULL
├── action              VARCHAR(50) NULL
├── source              VARCHAR(191) NULL
├── payload             JSON NOT NULL
├── status              VARCHAR(20) INDEX  (received | processing | processed | failed | ignored)
├── attempts            SMALLINT UNSIGNED DEFAULT 0
├── last_error          TEXT NULL
├── source_updated_at   TIMESTAMP NULL
├── received_at         TIMESTAMP NOT NULL
├── processed_at        TIMESTAMP NULL
└── created_at, updated_at
```

Reglas:

1. El controlador calcula `payload_hash = SHA256(cuerpo_crudo)` antes de decodificar el JSON.
2. El controlador inserta con `firstOrCreate` usando `payload_hash` como clave de idempotencia y guarda `delivery_id` para trazabilidad.
3. Si el evento ya existe, responde `200` sin volver a encolar.
4. El `delivery_id` de WooCommerce se genera con el ID del webhook y la marca de tiempo en segundos. Dos eventos distintos del mismo webhook en el mismo segundo comparten ID, por eso no se usa como clave única. El hash del payload evita perder el segundo evento.
5. Si WooCommerce reintenta con un `delivery_id` nuevo pero el mismo cuerpo, el `payload_hash` lo detecta y no duplica efectos.
6. El job verifica el estado antes de procesar.
7. Si el estado es `processed`, termina sin efectos.
8. Si el estado es `failed`, solo se reprocesa mediante un comando explícito o una política de reintento.
9. Los handlers usan `updateOrCreate` con claves de negocio:
   - `order_snapshots.wc_order_id`
   - `product_snapshots.wc_product_id`
   - `customer_snapshots.wc_customer_id`
10. Los movimientos de inventario usan una clave única lógica:
   - `origin = woocommerce_order`
   - `external_ref = order:{order_id}:item:{item_id}`
   - `wc_product_id`
   - `movement_type`
11. Los eventos desordenados se resuelven comparando `date_modified_gmt` del payload con `source_updated_at`. Un evento más antiguo se marca como `ignored`.
12. Un pedido que llega antes que su `order.created` se puede crear por upsert; el orden no debe romper el estado final.

Precisión sobre el identificador de entrega: en WooCommerce, `X-WC-Webhook-Delivery-ID` se genera con `wp_hash(webhook_id . strtotime('now'))`. No es un identificador global del evento de negocio; es un identificador de entrega con resolución de un segundo. Por eso la tabla guarda el `delivery_id` para auditoría, pero la clave única es el hash del payload. La idempotencia de dominio descrita en los puntos 9 y 10 es la última defensa.

#### Idempotencia en la escritura de stock

La actualización de stock desde Laravel no es idempotente por sí sola, porque cada movimiento cambia el saldo. Se protege con:

- Lock distribuido por producto: `stock:product:{id}`.
- Lectura de stock antes de escribir.
- Validación de negocio.
- Escritura por API.
- Verificación de lectura posterior.
- Registro del movimiento en una transacción local.
- Compensación si falla el registro.

#### Movimientos originados por pedidos

Los movimientos manuales tienen `stock_before` y `stock_after` exactos porque Laravel lee antes de escribir. Los movimientos originados por un pedido tienen una dificultad adicional: WooCommerce es quien descuenta el stock y Laravel se entera por un evento.

Procedimiento propuesto:

1. El webhook del pedido actualiza `order_snapshots`.
2. El handler determina si el pedido está en un estado en el que WooCommerce ya descontó stock. Para el flujo estándar, `processing` y `completed`; para Yape y Mercado Pago, el pedido pasa a `on-hold` y las pasarelas del proyecto ya reducen stock en ese momento.
3. Si el descuento está confirmado, se registra un movimiento `exit` por cada línea con `origin = woocommerce_order` y `external_ref = order:{order_id}:item:{item_id}`.
4. `stock_after` se obtiene por REST API al procesar el evento. `stock_before` se deriva del último snapshot conocido del producto y de la cantidad del pedido.
5. Si la diferencia no cuadra con el snapshot, no se inventa un saldo: se registra una alerta de reconciliación y el movimiento queda en `pending_review`.
6. Si el pedido se cancela o se reembolsa, se registra una reversión solo cuando WooCommerce confirma la restauración del stock.

Limitación declarada: en concurrencia alta, `stock_before` de un movimiento originado por pedido puede ser aproximado. La fuente de verdad sigue siendo la cantidad de WooCommerce, y la reconciliación diaria es la que detecta y explica cualquier diferencia. Para el alcance académico, esta aproximación es aceptable y está documentada; no se oculta como si fuera una transacción distribuida exacta.

### 5.6 Cola, reintentos y dead-letter

Componentes:

- **Redis** como backend de colas y cache.
- **Laravel Horizon** para supervisión de workers.
- **Colas separadas** por criticidad:
  - `webhooks`: procesamiento de eventos.
  - `inventory`: movimientos y reconciliación.
  - `notifications`: WhatsApp y correo.
  - `analytics`: GA4 y reportes.
  - `default`: tareas menores.

Política de ejecución:

| Parámetro | Valor propuesto |
|---|---|
| Intentos máximos | 5 |
| Backoff | 10 s, 30 s, 60 s, 300 s, 900 s |
| Timeout por job | 60 s |
| Cola de webhooks | 2 workers |
| Cola de inventario | 1 worker, serializado por producto |
| Cola de notificaciones | 1 worker |
| Respuesta al webhook | 200 en menos de 2 s |
| Reintento de WooCommerce | Automático ante respuesta no 2xx |
| Dead-letter | Tabla `failed_jobs` + `webhook_events.status = failed` |
| Replay | `php artisan webhooks:replay {id}` con auditoría |

Middlewares de job:

- `WithoutOverlapping` por `wc_product_id` o `wc_order_id`.
- `RateLimited` para llamadas a la API de WooCommerce.
- `FailOnTimeout`.
- `Backoff` declarativo.

Tareas programadas:

| Tarea | Frecuencia | Propósito |
|---|---|---|
| `webhooks:reconcile-orders` | Cada 15 minutos | Recuperar pedidos no recibidos por webhook |
| `sync:products` | Diaria, madrugada | Reconciliar catálogo y stock |
| `inventory:reconcile` | Diaria, madrugada | Comparar snapshots contra WooCommerce |
| `webhooks:prune` | Semanal | Depurar eventos procesados según retención |
| `analytics:retry` | Cada hora | Reenviar eventos de GA4 fallidos |
| `notifications:retry` | Cada 30 minutos | Reintentar notificaciones fallidas |

Dead-letter y operación:

- Un job que agota reintentos queda en `failed_jobs` y el evento se marca `failed`.
- Se alerta al administrador cuando la tasa de fallos supera el 1 % en 15 minutos o cuando hay más de 50 eventos pendientes.
- El replay es manual y auditado; no se reprograma automáticamente un evento fallido sin revisión.
- Los payloads se conservan según la política de retención, por defecto 90 días.

### 5.7 Ejemplo de código Laravel

#### Ruta

```php
<?php

// routes/api.php

use App\Http\Controllers\Api\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/woocommerce', WooCommerceWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.woocommerce');
```

La ruta se registra en el grupo `api`, que no aplica CSRF. No debe moverse al grupo `web`.

#### Controlador con verificación de firma

```php
<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessWooCommerceWebhook;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WooCommerceWebhookController
{
    public function __invoke(Request $request): Response
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-WC-Webhook-Signature', '');
        $secret = (string) config('services.woocommerce.webhook_secret');

        $expected = base64_encode(hash_hmac('sha256', $raw, $secret, true));

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            Log::warning('Webhook WooCommerce con firma invalida', [
                'ip' => $request->ip(),
                'topic' => $request->header('X-WC-Webhook-Topic'),
            ]);

            return response('Firma invalida', 401);
        }

        $deliveryId = (string) $request->header('X-WC-Webhook-Delivery-ID', '');
        $topic = (string) $request->header('X-WC-Webhook-Topic', '');

        if ($deliveryId === '' || $topic === '') {
            return response('Cabeceras incompletas', 422);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response('Payload invalido', 422);
        }

        $payloadHash = hash('sha256', $raw);

        $event = WebhookEvent::firstOrCreate(
            ['payload_hash' => $payloadHash],
            [
                'delivery_id' => $deliveryId,
                'topic' => $topic,
                'resource' => (string) $request->header('X-WC-Webhook-Resource', ''),
                'action' => (string) $request->header('X-WC-Webhook-Event', ''),
                'source' => (string) $request->header('X-WC-Webhook-Source', ''),
                'payload' => $payload,
                'status' => 'received',
                'received_at' => now(),
                'source_updated_at' => $payload['date_modified_gmt'] ?? null,
            ]
        );

        if ($event->wasRecentlyCreated) {
            ProcessWooCommerceWebhook::dispatch($event->id)->onQueue('webhooks');
        }

        return response('OK', 200);
    }
}
```

Nota: si la persistencia falla, se debe devolver `5xx` para que WooCommerce reintente. No se debe capturar la excepción y responder `200`.

Nota de rotación: el ejemplo valida el secreto actual. Durante la ventana de rotación se debe aceptar también `WC_WEBHOOK_SECRET_PREVIOUS`, sin relajar la comparación `hash_equals`.

#### Modelo `WebhookEvent`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'delivery_id',
        'payload_hash',
        'topic',
        'resource',
        'action',
        'source',
        'payload',
        'status',
        'attempts',
        'last_error',
        'source_updated_at',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'source_updated_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
    }
}
```

#### Migración de `webhook_events`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id')->index();
            $table->char('payload_hash', 64)->unique();
            $table->string('topic')->index();
            $table->string('resource')->nullable();
            $table->string('action')->nullable();
            $table->string('source')->nullable();
            $table->json('payload');
            $table->string('status')->default('received')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
```

#### Job de procesamiento

```php
<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WooCommerce\WooCommerceWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWooCommerceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(public int $webhookEventId)
    {
    }

    public function handle(WooCommerceWebhookHandler $handler): void
    {
        $event = WebhookEvent::findOrFail($this->webhookEventId);

        if ($event->status === 'processed') {
            return;
        }

        $event->increment('attempts');
        $event->markProcessing();

        try {
            $handler->handle($event);
            $event->markProcessed();
        } catch (Throwable $exception) {
            $event->markFailed($exception->getMessage());
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        WebhookEvent::whereKey($this->webhookEventId)->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
        ]);
    }
}
```

#### Handler por tópico

```php
<?php

namespace App\Services\WooCommerce;

use App\Models\WebhookEvent;

class WooCommerceWebhookHandler
{
    public function handle(WebhookEvent $event): void
    {
        match ($event->topic) {
            'order.created', 'order.updated' => app(HandleOrderWebhook::class)($event),
            'product.created', 'product.updated', 'product.deleted' => app(HandleProductWebhook::class)($event),
            'customer.created', 'customer.updated' => app(HandleCustomerWebhook::class)($event),
            default => null,
        };
    }
}
```

#### Configuración del cliente

```php
<?php

// config/services.php

return [
    'woocommerce' => [
        'base_url' => env('WC_BASE_URL'),
        'consumer_key' => env('WC_CONSUMER_KEY'),
        'consumer_secret' => env('WC_CONSUMER_SECRET'),
        'consumer_key_read_only' => env('WC_CONSUMER_KEY_READ_ONLY'),
        'consumer_secret_read_only' => env('WC_CONSUMER_SECRET_READ_ONLY'),
        'webhook_secret' => env('WC_WEBHOOK_SECRET'),
        'webhook_secret_previous' => env('WC_WEBHOOK_SECRET_PREVIOUS'),
        'api_version' => 'v3',
    ],
];
```

### 5.8 Evolución del contrato

- Los webhooks usan la versión v3 de la API de WooCommerce.
- Si WooCommerce cambia un payload, se agrega un campo; los handlers deben ignorar campos desconocidos.
- Los cambios incompatibles se gestionan con un adaptador por versión, no con condiciones dispersas.
- Cada release de Laravel incluye pruebas de contrato con payloads de ejemplo versionados en `tests/Fixtures/WooCommerce/`.
- La documentación del contrato vive en este archivo y en un ADR específico cuando se implemente.

---

## 6. Mapeo de requisitos académicos a la nueva arquitectura

La propuesta no cambia el estado actual de `docs/sprints/TRACEABILITY.md`. El mapeo indica dónde se cumplirá cada requisito cuando se ejecuten las fases.

| ID | Requisito | Dónde se cumple | Cómo se cumple | Evidencia futura |
|---|---|---|---|---|
| REQ-ECO-04 | Stock gestionado por WooCommerce | WordPress + WooCommerce | WooCommerce conserva `_stock` y `_stock_status`. Laravel solo lee y, cuando corresponde, escribe `stock_quantity` por REST API v3. Nunca hay SQL directo. | Captura de `_stock`, respuesta de `GET /products/{id}` y log de llamadas API |
| REQ-ECO-06 | Formulario WooCommerce en checkout | WordPress + WooCommerce | El checkout sigue siendo el de WooCommerce. Laravel no renderiza ni procesa el checkout. | Captura de `/checkout/`, UAT de Sprint 01 y prueba de pedido |
| REQ-ECO-07 | Pedido WooCommerce generado | WordPress + WooCommerce | WooCommerce crea el pedido. Laravel lo replica a partir de `order.created` y `GET /orders/{id}`. | Order ID en WooCommerce, fila en `order_snapshots` y `webhook_events` |
| REQ-ECO-08 | Stock descontado tras pedido | WordPress + WooCommerce | WooCommerce descuenta stock al confirmar. Cuando el pedido alcanza el estado con stock descontado, Laravel registra el movimiento de salida con `stock_after` leído por API y `stock_before` derivado del snapshot; una diferencia genera alerta. | Pedido de prueba con stock antes/después y fila en `inventory_movements` |
| REQ-INV-01 | Módulo de entradas | Laravel + Filament + WooCommerce | Formulario Filament crea una entrada, actualiza stock por API y registra el movimiento. | Movimiento `entry` con usuario, motivo y saldos |
| REQ-INV-02 | Módulo de salidas | Laravel + Filament + WooCommerce | Formulario Filament valida stock, actualiza por API y registra el movimiento. | Movimiento `exit` y prueba de salida válida |
| REQ-INV-03 | Módulo de ajustes | Laravel + Filament + WooCommerce | Formulario Filament fija el nuevo stock, actualiza por API y registra el movimiento. | Movimiento `adjustment` con stock anterior y nuevo |
| REQ-INV-04 | Kardex | Laravel | Vista Filament y exportación con historial cronológico por producto. | Captura del kardex y consulta SQL |
| REQ-INV-05 | Trazabilidad completa | Laravel | `inventory_movements` incluye usuario, fecha, motivo, tipo, cantidad, `stock_before`, `stock_after`, origen y referencia externa. | Esquema de tabla y fila de ejemplo |
| REQ-INV-06 | Movimientos de demostración | Laravel | Comando de seed o carga manual con al menos una entrada, una salida y un ajuste. | Tres filas de evidencia permanente |
| REQ-INV-07 | WooCommerce fuente única de stock | WordPress + WooCommerce | El plugin legado y el nuevo servicio Laravel usan la API de WooCommerce. No se duplica el stock en Laravel. | Código del cliente API, logs y verificación de que Laravel no tiene tabla de stock autoritativa |
| REQ-INV-08 | Validación salida mayor que stock | Laravel + WooCommerce | Validación en FormRequest, servicio de dominio y API. WooCommerce rechaza stock negativo. | Prueba automatizada y captura de error |
| REQ-ANA-01 | GA4 instalado | WordPress + Laravel | El frontend de WordPress carga gtag.js. Laravel complementa con Measurement Protocol y auditoría. | Captura de gtag y configuración de Measurement ID |
| REQ-ANA-02 | Evento `view_item` | WordPress | Se dispara en la página de producto, donde ocurre el evento. | DevTools y GA4 DebugView |
| REQ-ANA-03 | Evento `add_to_cart` | WordPress | Se dispara al añadir al carrito. | DevTools y GA4 DebugView |
| REQ-ANA-04 | Evento `begin_checkout` | WordPress | Se dispara al entrar al checkout. | DevTools y GA4 DebugView |
| REQ-ANA-05 | Evento `purchase` | WordPress + Laravel | El frontend lo envía; Laravel envía una copia server-side con `transaction_id` para deduplicar. | GA4 DebugView y `analytics_events` |
| REQ-ROL-02 | Rol Almacén | Laravel + WordPress | Se conserva la capability existente en WordPress y se crea un rol equivalente en Filament con permisos de inventario. | Captura de roles y prueba de acceso |
| REQ-ROL-03 | Rol Ventas | Laravel + WordPress | Se conserva la capability existente en WordPress y se crea un rol equivalente en Filament con permisos de pedidos y consulta. | Captura de roles y prueba de acceso |
| REQ-SEG-01 | HTTPS | Caddy + Cloudflare | Se mantiene TLS en el dominio principal y se agrega TLS en `admin.dominio`. | `curl -I https://...` y captura de Caddy |
| REQ-SEG-02 | MFA administradores | WordPress + Laravel | WordPress implementa su MFA según Sprint 04; el panel Laravel exige MFA para administradores. | Captura de configuración y prueba de login |
| REQ-SEG-03 | Backups periódicos | Infraestructura | Se respaldan ambos esquemas, uploads y storage de Laravel. | Script, cron y prueba de restauración |
| REQ-PRU-01 | Pruebas unitarias | Laravel + WordPress | Pruebas de dominio, firma HMAC, idempotencia y cliente API. | Reporte de test y CI |
| REQ-PRU-02 | UAT | Ambos | Se amplía el UAT con casos de integración y panel. | `docs/pruebas/UAT.md` actualizado |
| REQ-VER-01 | Versiones verificadas | Ambos | Se documentan versiones de Laravel, PHP, Redis y Filament al implementar. | Snapshot de versiones |

### 6.1 Aclaraciones académicas importantes

- **REQ-ECO-04 no se traslada a Laravel.** La cantidad de stock sigue viviendo en WooCommerce. Laravel solo conserva el historial de movimientos y solicita cambios por API.
- **REQ-ECO-06 y REQ-ECO-07 no se trasladan a Laravel.** El checkout y la creación del pedido siguen en WooCommerce. Si Laravel estuviera caído, el pedido se crea igual.
- **REQ-INV-07 se cumple con mayor claridad.** El mecanismo sigue siendo la API de WooCommerce; cambia el ejecutor de la interfaz, no la fuente de verdad.
- **REQ-ANA-01 a ANA-05 no se delegan completamente a Laravel.** Los eventos del embudo ocurren en el navegador del cliente y se instrumentan en WordPress. Laravel agrega el envío server-side de `purchase` y el registro de auditoría.
- **La documentación existente sigue siendo válida.** Los sprints 00 a 06 describen el estado y las evidencias del sistema. La propuesta agrega una fase de evolución, no reescribe el pasado.

---

## 7. Plan por fases

### 7.1 Resumen

| Fase | Objetivo | Duración estimada | Depende de |
|---|---|---|---|
| Fase 0 | Preparación, ADR, contrato y red | 1 semana | Nada |
| Fase 1 | Esqueleto Laravel y lectura por API | 2 semanas | Fase 0 |
| Fase 2 | Webhooks, cola y kardex | 2 a 3 semanas | Fase 1 |
| Fase 3 | Panel Filament y cutover de inventario | 2 semanas | Fase 2 |
| Fase 4 | Analítica, notificaciones y formularios | 1 a 2 semanas | Fase 2 |
| Fase 5 | Front público opcional | No comprometida | Fase 3 y 4 |

```text
Semana       1   2   3   4   5   6   7   8   9  10
Fase 0      ###
Fase 1          ######
Fase 2                ##########
Fase 3                          ######
Fase 4                                ######
Fase 5                                      ##  (opcional)
```

### 7.2 Fase 0: preparación

**Objetivo:** reducir el riesgo antes de escribir código.

**Entregables:**

- ADR con la decisión SOA, alternativas descartadas y consecuencias.
- Contrato de integración documentado: eventos, endpoints, campos, errores y versionado.
- Repositorio o carpeta `laravel/` con esqueleto mínimo.
- Archivo `docker-compose.soa.yml` como overlay, sin modificar el compose productivo actual.
- Secretos generados y almacenados fuera del repositorio.
- Plan de rollback documentado.
- Línea base de pruebas del WordPress actual.

**Criterio de aceptación:**

- [ ] El ADR está aprobado y enlazado desde este documento.
- [ ] `docker compose -f docker-compose.prod.yml -f docker-compose.soa.yml config` valida sin errores.
- [ ] WordPress sigue arrancando sin cambios de código.
- [ ] Existe un backup probado antes de cualquier cambio.
- [ ] Los secretos de webhook y API están definidos y no versionados.
- [ ] El rollback consiste en detener los servicios Laravel y quitar la ruta de Caddy, sin tocar WordPress.

### 7.3 Fase 1: esqueleto Laravel y lectura por API

**Objetivo:** demostrar que Laravel puede leer el comercio sin modificarlo.

**Entregables:**

- Aplicación Laravel con panel Filament básico.
- Autenticación de administradores.
- Cliente HTTP de WooCommerce REST API v3.
- Recursos de solo lectura para productos, pedidos y clientes.
- Comandos `sync:products`, `sync:orders`, `sync:customers`.
- Healthcheck `/up`.
- Pruebas con `Http::fake()` y una prueba de integración contra staging.

**Criterio de aceptación:**

- [ ] Los 12 productos del baseline aparecen en el panel.
- [ ] Los pedidos existentes, incluido el Order ID 60, aparecen en modo lectura.
- [ ] Ningún endpoint de escritura está habilitado.
- [ ] El cliente API usa la clave de solo lectura.
- [ ] Si WooCommerce no responde, el panel muestra un error controlado y no rompe WordPress.
- [ ] La tienda y el checkout siguen funcionando sin Laravel.

### 7.4 Fase 2: webhooks, cola y kardex

**Objetivo:** sincronizar pedidos y stock, y construir el kardex con trazabilidad.

**Entregables:**

- Endpoint `POST /api/webhooks/woocommerce`.
- Verificación HMAC SHA256 y pruebas de firma válida, inválida y ausente.
- Tabla `webhook_events` con idempotencia por `payload_hash` y trazabilidad por `delivery_id`.
- Redis, Horizon y jobs con backoff.
- Handlers para pedidos, productos y clientes.
- Tablas `order_snapshots`, `product_snapshots`, `customer_snapshots`, `inventory_movements`.
- Importación del historial legado de `wp_tanish_inventory_movements`.
- Movimientos manuales de entrada, salida y ajuste por API.
- Reconciliación de pedidos y stock.
- Comando de replay y alertas de fallos.

**Criterio de aceptación:**

- [ ] Un pedido nuevo en WooCommerce genera una fila en `webhook_events` y otra en `order_snapshots`.
- [ ] Un pedido pagado o procesado con dos líneas genera dos movimientos de salida.
- [ ] Un pedido en `pending` no genera salida hasta que WooCommerce descuente stock.
- [ ] Un webhook duplicado con el mismo payload no duplica filas aunque cambie el `delivery_id`.
- [ ] Dos eventos distintos del mismo webhook en el mismo segundo no se pierden.
- [ ] Un webhook con firma inválida responde `401` y no encola nada.
- [ ] Un job que falla reintenta con el backoff configurado y termina en `failed_jobs` si agota los intentos.
- [ ] Una salida mayor que el stock se rechaza antes de llamar a WooCommerce y también en WooCommerce.
- [ ] El kardex muestra movimientos nuevos y legados.
- [ ] La reconciliación detecta una diferencia y genera una alerta, sin inventar un saldo.

### 7.5 Fase 3: panel Filament y cutover de inventario

**Objetivo:** reemplazar la interfaz administrativa de inventario sin reemplazar WooCommerce.

**Entregables:**

- Recursos Filament para productos, pedidos, clientes, movimientos y kardex.
- Formularios de entrada, salida y ajuste con validaciones.
- Roles y políticas: administrador, almacén y ventas.
- MFA para administradores.
- Dashboard con stock bajo, movimientos recientes y alertas.
- Exportación de kardex a CSV.
- Auditoría de acciones.
- Modo de lectura para las pantallas legadas del plugin de WordPress.
- Guía de usuario.

**Criterio de aceptación:**

- [ ] Un usuario de almacén solo ve inventario y kardex.
- [ ] Un usuario de ventas solo ve productos, pedidos y consulta de stock.
- [ ] Un administrador conserva acceso completo.
- [ ] Cada movimiento registra usuario, motivo, tipo, cantidad y saldos.
- [ ] El stock en WooCommerce coincide con el último movimiento conciliado.
- [ ] Las pantallas legadas no permiten doble escritura.
- [ ] El UAT de inventario y roles se ejecuta y se documenta.

### 7.6 Fase 4: analítica, notificaciones y formularios

**Objetivo:** agregar valor operativo sin tocar el embudo comercial.

**Entregables:**

- Envío server-side de `purchase` a GA4 con `transaction_id`.
- Tabla `analytics_events` para auditoría y reintentos.
- Dashboard de eventos y ventas.
- Notificaciones WhatsApp para pedido creado, pedido pagado y stock bajo.
- Plantillas, opt-in, opt-out y log de mensajes.
- Recepción de formularios de contacto y reclamos en Laravel.
- Adaptador en WordPress que reenvía los envíos nuevos a Laravel, o comando de importación periódica; el CPT legado no se elimina.
- Reportes operativos.

**Criterio de aceptación:**

- [ ] `purchase` se envía una sola vez por pedido pagado.
- [ ] Un pedido en `on-hold` no dispara `purchase`.
- [ ] Cada notificación tiene registro, estado y reintentos.
- [ ] Un cliente sin opt-in no recibe notificaciones.
- [ ] Los formularios nuevos se administran en Filament y los legados siguen consultables.
- [ ] No se envían datos personales a GA4.

### 7.7 Fase 5: front público opcional

**Objetivo:** evaluar una experiencia de catálogo desacoplada, sin comprometer el requisito académico.

**Alcance:**

- Catálogo de solo lectura con WooCommerce Store API o REST API v3.
- Checkout y pedido siguen en WooCommerce.
- Feature flag para comparar contra el tema actual.
- No se elimina el tema WordPress.

**Criterio de aceptación:**

- [ ] Paridad funcional de catálogo, búsqueda y ficha de producto.
- [ ] El checkout sigue siendo WooCommerce.
- [ ] SEO, rendimiento y accesibilidad no se degradan.
- [ ] Existe rollback al tema actual con un cambio de configuración.
- [ ] La fase solo se ejecuta si el cronograma académico lo permite.

**Decisión recomendada:** no ejecutar la Fase 5 dentro del alcance académico obligatorio. El valor de la propuesta está en las fases 0 a 4. Un front adicional aumenta el riesgo de incumplir el requisito de WooCommerce y consume tiempo que conviene invertir en evidencias.

---

## 8. Despliegue propuesto

### 8.1 Principios

1. WordPress debe poder desplegarse solo, sin Laravel.
2. Laravel debe poder detenerse sin afectar la tienda.
3. La base de datos y Redis no se publican a Internet.
4. Solo Caddy publica puertos.
5. Los servicios Laravel comparten imagen y configuración.
6. Los secretos se inyectan por variables de entorno o Docker secrets.
7. El compose productivo actual no se reescribe: se extiende con un overlay.

### 8.2 Overlay de Docker Compose

Archivo propuesto: `docker-compose.soa.yml`.

```yaml
services:
  laravel:
    build:
      context: ./laravel
      dockerfile: Dockerfile
    image: tanish-laravel:${TANISH_VERSION:-local}
    container_name: tanish-prod-laravel
    restart: unless-stopped
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    environment:
      APP_NAME: TANISH
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: https://admin.${CADDY_DOMAIN}
      APP_KEY: ${LARAVEL_APP_KEY}
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: "3306"
      DB_DATABASE: tanish_laravel
      DB_USERNAME: tanish_laravel
      DB_PASSWORD: ${LARAVEL_DB_PASSWORD}
      REDIS_HOST: redis
      REDIS_PORT: "6379"
      QUEUE_CONNECTION: redis
      CACHE_STORE: redis
      SESSION_DRIVER: redis
      WC_BASE_URL: http://wordpress:80
      WC_CONSUMER_KEY: ${WC_CONSUMER_KEY}
      WC_CONSUMER_SECRET: ${WC_CONSUMER_SECRET}
      WC_CONSUMER_KEY_READ_ONLY: ${WC_CONSUMER_KEY_READ_ONLY}
      WC_CONSUMER_SECRET_READ_ONLY: ${WC_CONSUMER_SECRET_READ_ONLY}
      WC_WEBHOOK_SECRET: ${WC_WEBHOOK_SECRET}
      WC_WEBHOOK_SECRET_PREVIOUS: ${WC_WEBHOOK_SECRET_PREVIOUS:-}
      GA4_MEASUREMENT_ID: ${GA4_MEASUREMENT_ID:-}
      GA4_API_SECRET: ${GA4_API_SECRET:-}
      WHATSAPP_DRIVER: ${WHATSAPP_DRIVER:-log}
      WHATSAPP_TOKEN: ${WHATSAPP_TOKEN:-}
      WHATSAPP_PHONE_NUMBER_ID: ${WHATSAPP_PHONE_NUMBER_ID:-}
      WHATSAPP_ADMIN_NUMBER: ${WHATSAPP_ADMIN_NUMBER:-}
      LOG_CHANNEL: stderr
      LOG_LEVEL: info
    volumes:
      - laravel_storage:/var/www/html/storage
    expose:
      - "8000"
    healthcheck:
      test: ["CMD-SHELL", "curl -fsS http://127.0.0.1:8000/up >/dev/null 2>&1 || exit 1"]
      interval: 15s
      timeout: 5s
      retries: 10
      start_period: 30s
    networks:
      - tanish-network

  laravel-queue:
    image: tanish-laravel:${TANISH_VERSION:-local}
    container_name: tanish-prod-laravel-queue
    restart: unless-stopped
    depends_on:
      laravel:
        condition: service_healthy
      redis:
        condition: service_healthy
    command: ["php", "artisan", "horizon"]
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: ${LARAVEL_APP_KEY}
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: "3306"
      DB_DATABASE: tanish_laravel
      DB_USERNAME: tanish_laravel
      DB_PASSWORD: ${LARAVEL_DB_PASSWORD}
      REDIS_HOST: redis
      REDIS_PORT: "6379"
      QUEUE_CONNECTION: redis
      CACHE_STORE: redis
      WC_BASE_URL: http://wordpress:80
      WC_CONSUMER_KEY: ${WC_CONSUMER_KEY}
      WC_CONSUMER_SECRET: ${WC_CONSUMER_SECRET}
      WC_WEBHOOK_SECRET: ${WC_WEBHOOK_SECRET}
      LOG_CHANNEL: stderr
    volumes:
      - laravel_storage:/var/www/html/storage
    networks:
      - tanish-network

  laravel-scheduler:
    image: tanish-laravel:${TANISH_VERSION:-local}
    container_name: tanish-prod-laravel-scheduler
    restart: unless-stopped
    depends_on:
      laravel:
        condition: service_healthy
    command: ["php", "artisan", "schedule:work"]
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: ${LARAVEL_APP_KEY}
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: "3306"
      DB_DATABASE: tanish_laravel
      DB_USERNAME: tanish_laravel
      DB_PASSWORD: ${LARAVEL_DB_PASSWORD}
      REDIS_HOST: redis
      REDIS_PORT: "6379"
      QUEUE_CONNECTION: redis
      CACHE_STORE: redis
      WC_BASE_URL: http://wordpress:80
      WC_CONSUMER_KEY: ${WC_CONSUMER_KEY}
      WC_CONSUMER_SECRET: ${WC_CONSUMER_SECRET}
      WC_WEBHOOK_SECRET: ${WC_WEBHOOK_SECRET}
      LOG_CHANNEL: stderr
    volumes:
      - laravel_storage:/var/www/html/storage
    networks:
      - tanish-network

  redis:
    image: redis:7-alpine
    container_name: tanish-prod-redis
    restart: unless-stopped
    command: ["redis-server", "--appendonly", "yes"]
    volumes:
      - redis_data:/data
    expose:
      - "6379"
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks:
      - tanish-network

volumes:
  laravel_storage:
    name: tanish_laravel_storage_prod
  redis_data:
    name: tanish_redis_data_prod
```

Notas:

- `laravel-queue` y `laravel-scheduler` usan la misma imagen que `laravel`; se construye una vez.
- No se publica ningún puerto de Laravel, Redis ni MySQL.
- `laravel_storage` persiste logs, caché de vistas, sesiones de archivo si se usaran y archivos generados.
- Redis usa `appendonly` para reducir pérdida de colas ante reinicio, aunque la fuente de verdad de los eventos es MySQL.

### 8.3 Base de datos

Se reutiliza la instancia MySQL 8 existente por una razón de recursos del VPS. Se crean un esquema y un usuario exclusivos:

```sql
CREATE DATABASE IF NOT EXISTS tanish_laravel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'tanish_laravel'@'%'
  IDENTIFIED BY 'CAMBIAR_EN_PRODUCCION';

GRANT ALL PRIVILEGES ON tanish_laravel.* TO 'tanish_laravel'@'%';
FLUSH PRIVILEGES;
```

Importante:

- El script de inicialización de MySQL solo se ejecuta cuando el volumen está vacío. En una instalación existente, el esquema y el usuario se crean manualmente una vez.
- El usuario `tanish_laravel` no recibe privilegios sobre `tanish_wordpress`.
- Laravel no puede hacer `JOIN` entre ambos esquemas.
- Si el VPS se queda corto de memoria, la evolución es mover MySQL a un contenedor o servidor dedicado sin cambiar el contrato.

### 8.4 Caddy

Se agrega un bloque para el panel, sin tocar el bloque principal de WordPress:

```caddyfile
{$CADDY_DOMAIN} {
  encode gzip
  reverse_proxy wordpress:80
}

admin.{$CADDY_DOMAIN} {
  encode gzip
  header {
    Strict-Transport-Security "max-age=31536000; includeSubDomains"
    X-Content-Type-Options "nosniff"
    X-Frame-Options "DENY"
    Referrer-Policy "strict-origin-when-cross-origin"
  }
  reverse_proxy laravel:8000
}

http://185.176.254.183.nip.io {
  encode gzip
  reverse_proxy wordpress:80 {
    header_up Host tanish-proxy.solvegrades.workers.dev
  }
}

:80 {
  respond 404
}
```

DNS:

- `tanish.dominio.com` apunta al VPS.
- `admin.dominio.com` apunta al VPS.
- Cloudflare puede mantener ambos proxificados.
- Caddy obtiene o renueva TLS según la configuración existente.

El webhook interno no necesita una ruta pública. WordPress entrega a `http://laravel:8000/api/webhooks/woocommerce` dentro de `tanish-network`. Si se decide usar la ruta pública, se usa `https://admin.dominio.com/api/webhooks/woocommerce` y se aplica límite de tasa.

### 8.5 Variables de entorno

Archivo: `.env.production` en el VPS, con permisos `600`, nunca versionado. El `.gitignore` actual ya ignora `.env` y `.env.*`.

| Variable | Ejemplo | Propósito |
|---|---|---|
| `LARAVEL_APP_KEY` | `base64:...` | Cifrado de Laravel |
| `APP_URL` | `https://admin.tanish.example.com` | URL del panel |
| `LARAVEL_DB_PASSWORD` | secreto | Usuario de `tanish_laravel` |
| `WC_BASE_URL` | `http://wordpress:80` | URL base interna; el cliente agrega `/wp-json/wc/v3` |
| `WC_CONSUMER_KEY` | `ck_...` | Clave de escritura acotada |
| `WC_CONSUMER_SECRET` | `cs_...` | Secreto de escritura acotada |
| `WC_CONSUMER_KEY_READ_ONLY` | `ck_...` | Clave de lectura |
| `WC_CONSUMER_SECRET_READ_ONLY` | `cs_...` | Secreto de lectura |
| `WC_WEBHOOK_SECRET` | secreto largo | Firma HMAC de webhooks |
| `WC_WEBHOOK_SECRET_PREVIOUS` | secreto anterior | Rotación |
| `GA4_MEASUREMENT_ID` | `G-...` | Measurement Protocol |
| `GA4_API_SECRET` | secreto | Measurement Protocol |
| `WHATSAPP_DRIVER` | `log` o `cloud` | Modo de notificación |
| `WHATSAPP_TOKEN` | token | WhatsApp Cloud API |
| `WHATSAPP_PHONE_NUMBER_ID` | ID | WhatsApp Cloud API |
| `WHATSAPP_ADMIN_NUMBER` | `519...` | Destino de alertas |
| `LOG_CHANNEL` | `stderr` | Logs a Docker |
| `LOG_LEVEL` | `info` | Nivel de log |
| `SENTRY_DSN` | opcional | Monitoreo de errores |

Reglas:

- `APP_DEBUG=false` en producción.
- `TELESCOPE_ENABLED=false` en producción.
- Las claves de WooCommerce se generan en `WooCommerce > Ajustes > Avanzado > REST API`.
- La clave de escritura se usa solo para stock; no se expone al frontend.
- El secreto de webhook no se reutiliza como secreto de API.

### 8.6 Secuencia de despliegue

```bash
# 1. Validar la composicion
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml config

# 2. Construir la imagen de Laravel
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml build laravel

# 3. Ejecutar migraciones
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml run --rm laravel php artisan migrate --force

# 4. Levantar servicios nuevos
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml up -d redis laravel laravel-queue laravel-scheduler

# 5. Optimizar
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml exec laravel php artisan optimize

# 6. Verificar
curl -fsS https://admin.tanish.example.com/up
docker compose --env-file .env.production \
  -f docker-compose.prod.yml \
  -f docker-compose.soa.yml exec laravel php artisan horizon:status
```

No se ejecuta `docker compose down -v`. No se recrean WordPress ni MySQL. Si Caddy cambia su configuración, se recarga el servicio `caddy`.

### 8.7 Rollback

| Escenario | Acción |
|---|---|
| Laravel no arranca | Detener `laravel`, `laravel-queue`, `laravel-scheduler`; WordPress sigue operativo |
| Webhooks fallan | Desactivar webhooks en WooCommerce; la tienda sigue vendiendo |
| Panel con error | Quitar el bloque `admin.` de Caddy; el dominio principal no se afecta |
| Migración fallida | Restaurar backup de `tanish_laravel`; no tocar `tanish_wordpress` |
| Cambio de contrato | Revertir la imagen a la etiqueta anterior |
| Pérdida de datos | Restaurar MySQL y storage; Redis se reconstruye |

### 8.8 Desarrollo local

- Mantener `docker-compose.yml` actual.
- Agregar `docker-compose.soa.yml` con `APP_ENV=local`, `APP_DEBUG=true` y `WHATSAPP_DRIVER=log`.
- Usar Mailpit existente para correo.
- Usar una base `tanish_laravel` local.
- Ejecutar `php artisan horizon` y `php artisan schedule:work` en contenedores separados.
- El webhook local puede apuntar a `http://laravel:8000` o a `http://host.docker.internal:8000` si Laravel corre fuera de Docker.

---

## 9. Seguridad y operación

### 9.1 Gestión de secretos

| Secreto | Almacenamiento | Rotación | Acceso |
|---|---|---|---|
| `APP_KEY` | `.env.production` o Docker secret | Solo si hay compromiso | Servicios Laravel |
| Claves WooCommerce | `.env.production` | Trimestral o ante incidente | Servicio de integración |
| Secreto de webhook | `.env.production` y WooCommerce | Semestral o ante incidente | Controlador y WooCommerce |
| Contraseña MySQL | `.env.production` | Trimestral | Laravel y MySQL |
| Token WhatsApp | `.env.production` | Según proveedor | Jobs de notificación |
| `GA4_API_SECRET` | `.env.production` | Anual | Jobs de analítica |

Buenas prácticas:

- Nunca imprimir secretos en logs.
- Redactar cabeceras sensibles.
- No incluir payloads completos en logs de aplicación; se guardan en base de datos con acceso restringido.
- Usar usuarios distintos por servicio.
- Revocar claves de WooCommerce que no se usen.
- Auditar el acceso al `.env.production`.

### 9.2 Autenticación y autorización

- Laravel tiene su propia tabla de usuarios; no comparte cookies ni hashes con WordPress.
- Los correos de los usuarios pueden coincidir, pero las cuentas son independientes.
- Roles de Filament:
  - `admin`: acceso completo.
  - `almacen`: inventario y kardex.
  - `ventas`: productos, pedidos y consulta de stock.
- MFA obligatorio para `admin`.
- Registro público deshabilitado.
- Sesiones con expiración y revocación.
- Políticas por recurso y por acción.
- Auditoría de cambios en movimientos y pedidos.

No se implementa SSO en el alcance obligatorio. Si se requiere, se evalúa OIDC con WordPress como proveedor o un proveedor externo, fuera de las fases 0 a 4.

### 9.3 Límites de tasa y exposición

- Webhook: `throttle:120,1` en Laravel.
- Login: límite de intentos y bloqueo temporal.
- API de WooCommerce: límite de concurrencia y backoff.
- Cloudflare: reglas de tasa y protección de bots para `admin.dominio`.
- Sin puertos publicados para MySQL, Redis, WordPress y Laravel.
- Sin acceso directo a la base de datos desde Internet.
- Sin `APP_DEBUG=true` en producción.
- Sin listados de directorios.

### 9.4 Logs

- Laravel escribe a `stderr`, recolectado por Docker.
- Formato JSON en producción para facilitar búsquedas.
- Cada log de webhook incluye `delivery_id`, `topic` y `status`.
- No se registran teléfonos, correos completos ni direcciones.
- Retención de logs de aplicación: 30 días.
- Retención de payloads en base de datos: 90 días, configurable.
- Los logs de auditoría de movimientos se conservan según la política académica y operativa.

### 9.5 Monitoreo

| Señal | Herramienta | Umbral |
|---|---|---|
| Disponibilidad del panel | Healthcheck `/up` + Uptime Kuma | 2 fallos consecutivos |
| Disponibilidad de la tienda | Healthcheck de WordPress | 2 fallos consecutivos |
| Cola atascada | Horizon | Más de 50 jobs pendientes por 10 minutos |
| Fallos de webhook | `webhook_events` | Más de 1 % en 15 minutos |
| Jobs fallidos | `failed_jobs` | Más de 5 en 1 hora |
| Errores 5xx | Logs y Sentry opcional | Más de 10 en 5 minutos |
| Latencia de API WooCommerce | Métricas de Laravel | p95 mayor a 3 segundos |
| Disco | Docker y host | Más de 80 % |
| Memoria Redis | Redis INFO | Más de 80 % |
| Reconciliación | Reporte diario | Cualquier diferencia no explicada |

Alertas:

- Correo al administrador.
- WhatsApp al número operativo, usando el mismo canal de notificaciones.
- Panel de Filament con widgets de estado.

### 9.6 Backups

| Elemento | Frecuencia | Retención | Método |
|---|---|---|---|
| `tanish_wordpress` | Diaria | 7 días | `mysqldump` comprimido |
| `tanish_laravel` | Diaria | 7 días | `mysqldump` comprimido |
| Uploads de WordPress | Diaria | 7 días | Tar comprimido |
| Storage de Laravel | Diaria | 7 días | Tar comprimido |
| Configuración y secretos | Antes de cada cambio | 30 días | Cofre seguro, no Git |
| Redis | No se respalda | No aplica | Colas reconstruibles; eventos en MySQL |

Reglas:

- Probar la restauración al menos una vez por trimestre.
- Un backup no se considera válido hasta que se restaura en un entorno de prueba.
- El backup de Laravel no reemplaza el de WordPress.
- El script `scripts/backup-production.sh` se extiende para incluir `tanish_laravel` y `laravel_storage`.

### 9.7 Actualizaciones y cambios

- Versiones de imágenes fijadas por etiqueta, no `latest` en producción.
- Actualizaciones de WordPress y WooCommerce primero en staging.
- Pruebas de contrato con payloads de ejemplo antes de actualizar.
- Actualizaciones de Laravel y Filament con ventana de mantenimiento.
- Migraciones siempre hacia adelante y con backup previo.
- Rollback por etiqueta de imagen y restauración de base de datos.

### 9.8 Runbook de incidentes

| Incidente | Acción inmediata | Verificación |
|---|---|---|
| Laravel caído | La tienda sigue; revisar contenedor y logs | Checkout y pedido en WooCommerce |
| Webhooks fallando | Revisar firma, red y cola; desactivar webhook si es necesario | `webhook_events` y reconciliación |
| Cola atascada | Reiniciar Horizon; revisar jobs fallidos | Backlog y latencia |
| Stock desincronizado | Ejecutar reconciliación; no ajustar a mano sin registrar movimiento | Comparación API vs kardex |
| Pedido no replicado | Buscar por ID en WooCommerce y ejecutar replay o sincronización | `order_snapshots` |
| Fuga de secreto | Rotar clave, revocar la anterior, revisar accesos | Logs y claves activas |
| Disco lleno | Limpiar logs y backups antiguos; ampliar volumen | Espacio libre |

---

## 10. Riesgos y mitigaciones

| ID | Riesgo | Probabilidad | Impacto | Mitigación | Señal temprana |
|---|---|---|---|---|---|
| R-01 | Consistencia eventual entre stock y kardex | Media | Medio | Reconciliación diaria, locks por producto, registro de diferencias sin inventar saldos | Reporte de diferencias |
| R-02 | Webhooks perdidos | Media | Alto | Reconciliación cada 15 minutos por REST API, reintentos y alertas | Pedido sin snapshot |
| R-03 | Webhooks duplicados | Alta | Medio | `payload_hash` único, `delivery_id` para trazabilidad, `updateOrCreate`, claves de negocio | Filas duplicadas en pruebas |
| R-04 | Webhooks desordenados | Media | Medio | Comparar `date_modified_gmt`, ignorar eventos antiguos | Evento `ignored` |
| R-05 | Condición de carrera en stock | Media | Alto | Lock Redis por producto, serialización de jobs, verificación posterior | Diferencia entre API y kardex |
| R-06 | Doble despliegue y deriva de versiones | Media | Medio | CI para ambos servicios, etiquetas inmutables, contrato versionado | Fallo de pruebas de contrato |
| R-07 | Acoplamiento a detalles internos de WooCommerce | Media | Alto | Usar solo REST API v3 y webhooks; no SQL; pruebas de contrato | Payload inesperado |
| R-08 | Autenticación duplicada y confusión de roles | Media | Medio | Cuentas separadas, roles equivalentes, documentación y MFA | Usuario con permisos incorrectos |
| R-09 | Fuga de secretos | Baja | Alto | `.env` fuera de Git, permisos 600, rotación, logs redactados | Clave expuesta en repositorio |
| R-10 | Saturación del VPS de 2 GB | Media | Medio | Límites de memoria, Redis pequeño, monitoreo, separar MySQL si crece | Reinicios por OOM |
| R-11 | Actualización de WooCommerce rompe el contrato | Media | Alto | Staging, pruebas de contrato, adaptadores por versión | Errores de validación de payload |
| R-12 | Laravel se vuelve un monolito distribuido | Media | Alto | Prohibir SQL cruzado, separar esquemas, revisiones de arquitectura | Consultas a tablas `wp_` |
| R-13 | Alcance académico se desborda | Alta | Alto | Fases con criterios de aceptación, Fase 5 opcional, priorización | Retraso en evidencias |
| R-14 | Dependencia de un solo VPS | Media | Alto | Backups, documentación de reconstrucción, monitoreo | Caída prolongada |
| R-15 | PII en logs o GA4 | Media | Alto | Redacción, minimización, revisión de payloads | Correo o teléfono en logs |
| R-16 | Pérdida de evidencia académica | Baja | Alto | No borrar tablas legadas, conservar capturas, versionar documentos | Evidencia faltante en sustentación |

### 10.1 Riesgo principal y respuesta

El riesgo principal es que la integración se perciba como un reemplazo encubierto de WooCommerce. La respuesta es explícita:

- El checkout, el pedido y el stock permanecen en WooCommerce.
- Laravel no tiene tablas de pedidos ni de stock autoritativas.
- El kardex es un libro de movimientos, no la cantidad disponible.
- La tienda funciona si Laravel se apaga.
- La demostración debe iniciar en WooCommerce y terminar en WooCommerce.

### 10.2 Riesgo de consistencia: tratamiento honesto

En un sistema distribuido no existe una transacción única entre la API de WooCommerce y la base de Laravel. La propuesta no promete consistencia fuerte. Promete:

- Consistencia eventual con reconciliación.
- Registro de diferencias en lugar de correcciones silenciosas.
- Idempotencia para evitar duplicados.
- Locks para reducir carreras.
- Evidencia de cada movimiento.

Ese es el nivel correcto para un MVP académico y es superior a un `UPDATE` directo a `wp_postmeta` sin auditoría.

---

## 11. Cómo presentarlo al profesor

### 11.1 Texto listo para citar

> La solución mantiene WordPress con WooCommerce como plataforma de comercio electrónico exigida por el proyecto. No se reemplaza, no se elimina y no se reduce a un componente secundario: el catálogo, el carrito, el checkout, la creación de pedidos, el descuento de stock y las pasarelas de pago siguen ejecutándose en WooCommerce. La evidencia de los sprints 00 a 06 sigue siendo válida.
>
> Sobre esa base se incorpora Laravel como un segundo servicio, orientado a la integración y a la administración. Laravel no vende, no cobra y no crea pedidos. Consume los webhooks nativos de WooCommerce, lee datos con la REST API v3 y ofrece un panel con Filament para inventario, kardex, notificaciones, analítica y reportes. Cuando el personal registra una entrada, una salida o un ajuste, Laravel actualiza el stock a través de la API de WooCommerce y deja el movimiento auditado con usuario, motivo, stock anterior y stock posterior.
>
> La arquitectura resultante es una SOA de dos servicios, no una arquitectura de microservicios. Los servicios son gruesos, tienen contratos explícitos y comparten infraestructura. WordPress sigue siendo el sistema de registro del comercio; Laravel es el sistema de registro de los movimientos administrativos y de la trazabilidad. No hay acceso cruzado a las bases de datos ni transacciones distribuidas. La integración es asíncrona, idempotente y reconciliable.
>
> El valor agregado es verificable: un pedido creado en WooCommerce genera un webhook firmado, Laravel lo valida con HMAC SHA256, lo registra, lo procesa en cola y actualiza el kardex; el panel muestra el pedido, el movimiento y la alerta si algo no cuadra. Si Laravel se detiene, la tienda y el checkout siguen operando. Si un webhook se pierde, la reconciliación lo recupera. Si el stock cambia, la fuente de verdad sigue siendo WooCommerce.
>
> En consecuencia, el requisito académico se cumple y se amplía: se conserva la plataforma exigida y se demuestra una integración real entre dos tecnologías, con seguridad, idempotencia, colas, observabilidad y documentación de arquitectura.

### 11.2 Respuesta a la objeción "esto ya no sería WooCommerce"

> WooCommerce sigue siendo el único lugar donde se crea un pedido y donde se descuenta el stock por una venta. Laravel no tiene una tabla de pedidos que reemplace a WooCommerce ni una tabla de stock que compita con WooCommerce. Tiene snapshots de lectura, un libro de movimientos y un panel de administración. La prueba es simple: si se apaga Laravel, el cliente puede comprar y el pedido se crea en WooCommerce; si se apaga WooCommerce, Laravel no puede vender. Esa asimetría demuestra cuál es el núcleo.

### 11.3 Demostración sugerida

1. Mostrar el catálogo y el checkout en WordPress.
2. Crear un pedido de prueba y mostrar el descuento de stock en WooCommerce.
3. Mostrar el webhook configurado en `WooCommerce > Ajustes > Avanzado > Webhooks`.
4. Mostrar `webhook_events` en Laravel con el `delivery_id`, la firma validada y el estado `processed`.
5. Mostrar el pedido replicado y el movimiento de salida en el kardex.
6. Registrar una entrada manual desde Filament y verificar el nuevo stock en WooCommerce.
7. Intentar una salida mayor que el stock y mostrar el rechazo.
8. Detener Laravel y repetir una compra para demostrar que WooCommerce sigue operando.
9. Reiniciar Laravel y mostrar la reconciliación.

### 11.4 Qué decir si preguntan por microservicios

> No se usaron microservicios porque el problema no los requiere. Hay dos servicios con responsabilidades gruesas, un contrato claro y una infraestructura compartida. Microservicios implicaría descomponer WooCommerce, que es justamente la plataforma que el proyecto debe conservar. La SOA permite separar responsabilidades sin introducir la complejidad operativa de una malla de servicios.

### 11.5 Qué decir si preguntan por la base de datos

> Hay dos esquemas en una misma instancia MySQL por economía de recursos, pero con usuarios y permisos separados. Laravel no puede leer ni escribir las tablas de WordPress. La comunicación es por API y webhooks. Si mañana se separan los servidores, el código no cambia. Compartir la instancia no significa compartir el modelo de datos.

---

## 12. Definition of Done y criterios de aceptación

### 12.1 Definition of Done técnica

Una fase se considera terminada cuando:

- [ ] El código está versionado y revisado.
- [ ] Las migraciones se ejecutan desde cero y sobre una base existente.
- [ ] Las pruebas unitarias y de integración pasan.
- [ ] La firma HMAC tiene pruebas de caso válido, inválido y ausente.
- [ ] La idempotencia tiene una prueba con entrega duplicada.
- [ ] Los jobs tienen backoff, timeout y manejo de fallos.
- [ ] Los logs no exponen secretos ni datos personales.
- [ ] La documentación de la fase está actualizada.
- [ ] El rollback está probado.
- [ ] WordPress sigue funcionando sin Laravel.

### 12.2 Definition of Done académica

- [ ] El requisito correspondiente está mapeado en este documento.
- [ ] La evidencia está guardada en `docs/evidencias/`.
- [ ] `docs/sprints/TRACEABILITY.md` se actualiza con el nuevo estado.
- [ ] El UAT incluye el caso de integración.
- [ ] La arquitectura está explicada en lenguaje comprensible para la sustentación.
- [ ] Se puede demostrar que WooCommerce sigue siendo el núcleo del comercio.

### 12.3 Criterios de aceptación globales

| Criterio | Medición |
|---|---|
| WordPress sigue operativo sin Laravel | Checkout y pedido funcionan con Laravel detenido |
| WooCommerce sigue siendo la fuente de stock | `stock_quantity` coincide con el último movimiento conciliado |
| El pedido se crea en WooCommerce | Order ID visible en WooCommerce y en `order_snapshots` |
| El webhook se verifica | Firma inválida devuelve 401 y no se procesa |
| No hay duplicados | Mismo payload no genera dos filas; dos eventos distintos en el mismo segundo no se pierden |
| El kardex es trazable | Cada movimiento tiene usuario, fecha, motivo, tipo, cantidad, antes y después |
| La salida excede stock se bloquea | Error controlado antes y después de la API |
| La cola reintenta | Job fallido reintenta con backoff y queda en `failed_jobs` |
| La reconciliación corrige | Pedido omitido se recupera por API en la siguiente ejecución |
| El panel tiene roles | Almacén, ventas y administrador con permisos distintos |
| La analítica no duplica | `purchase` se envía una vez por pedido pagado |
| Las notificaciones son auditables | Cada mensaje tiene estado, intento y resultado |
| Los secretos están protegidos | `.env.production` fuera de Git y permisos restringidos |
| Los backups se restauran | Prueba de restauración documentada |
| La documentación académica es válida | Sprints 00 a 06 conservados y nuevos anexos agregados |

### 12.4 No se considera terminado si

- Laravel es necesario para completar una compra.
- Laravel escribe directamente en `wp_postmeta` o `wp_posts`.
- El stock se duplica en una tabla de Laravel como si fuera autoritativa.
- Un webhook se procesa sin verificar la firma.
- Un webhook duplicado genera un segundo movimiento.
- Un fallo de Laravel bloquea el checkout.
- El panel no tiene control de roles.
- No hay evidencia de reconciliación.
- La documentación existente se reescribe para ocultar el estado anterior.
- Se elimina el historial legado de inventario.

### 12.5 Paquete de evidencias para la sustentación

- Diagrama de arquitectura actual y propuesto.
- ADR de la decisión SOA.
- Captura de webhooks configurados en WooCommerce.
- Captura de `webhook_events` con firma validada.
- Captura del kardex con movimientos legados y nuevos.
- Captura del stock en WooCommerce antes y después.
- Prueba de salida mayor que stock.
- Prueba de Laravel detenido y tienda operativa.
- Prueba de reconciliación.
- Resultados de pruebas automatizadas.
- UAT actualizado.
- `TRACEABILITY.md` actualizado.
- Este documento y `ARQUITECTURA-ACTUAL.md`.

---

## 13. Referencias a archivos del repositorio

| Archivo | Qué aporta a la propuesta |
|---|---|
| `docs/arquitectura/ARQUITECTURA-ACTUAL.md` | Línea base del monolito modular y sus limitaciones |
| `docs/arquitectura/README.md` | Principio de la propuesta y orden de lectura |
| `docs/sprints/TRACEABILITY.md` | Requisitos, estados y evidencia académica que no se debe invalidar |
| `docs/sprints/SPRINT-00-BASELINE.md` | Versiones verificadas, 12 productos, 15 categorías, HPOS y estado de componentes |
| `docs/sprints/SPRINT-01-B2C-COMMERCE.md` | Flujo B2C, Order ID 60, stock 30 → 29 y restauración de add_to_cart |
| `docs/sprints/SPRINT-02-ROLES-INVENTORY.md` | Roles, movimientos de inventario y criterios de kardex |
| `docs/sprints/SPRINT-03-ANALYTICS.md` | Eventos GA4 requeridos y dependencia del frontend |
| `docs/sprints/SPRINT-04-SECURITY.md` | HTTPS, MFA, backups y controles de seguridad |
| `docs/sprints/SPRINT-05-TESTING-EVIDENCE.md` | Pruebas y evidencias esperadas |
| `docs/sprints/SPRINT-06-ACADEMIC-ALIGNMENT.md` | Alineación con el documento académico |
| `docker-compose.yml` | Servicios locales, red `tanish-network`, Mailpit y cron de desarrollo |
| `docker-compose.prod.yml` | Servicios productivos, volúmenes, healthchecks y red compartida |
| `caddy/Caddyfile` | Enrutamiento actual, TLS y ruta del Worker |
| `plugins/tanish-inventory/tanish-inventory.php` | Bootstrap del plugin, versión y carga de clases |
| `plugins/tanish-inventory/includes/class-tanish-inventory-service.php` | Reglas de entrada, salida y ajuste; validación de stock; uso de la API de WooCommerce |
| `plugins/tanish-inventory/includes/class-tanish-inventory-database.php` | Esquema de `wp_tanish_inventory_movements` y consultas de kardex |
| `plugins/tanish-inventory/includes/class-tanish-inventory-capabilities.php` | Roles `almacen` y `ventas`, capability `manage_tanish_inventory` |
| `plugins/tanish-inventory/includes/class-tanish-whatsapp.php` | Canal WhatsApp y configuración del número |
| `plugins/tanish-inventory/includes/class-tanish-yape-gateway.php` | Pasarela Yape simulada y reducción de stock |
| `plugins/tanish-inventory/includes/class-tanish-mercadopago-link.php` | Pasarela Mercado Pago por link y pedido en espera |
| `mu-plugins/tanish-cron.php` | Ejecución de cron en desarrollo y Action Scheduler |
| `mu-plugins/tanish-forms.php` | Formularios de contacto y reclamos en CPT |
| `docs/DEPLOYMENT.md` | Flujo de despliegue en VPS, backups, HTTPS y rollback |
| `docs/academic/6.3-despliegue.md` | Estado de entornos, componentes y estrategia de backup |
| `docs/pruebas/UAT.md` | Casos de aceptación que se amplían con la integración |
| `.env.production.example` | Variables de producción existentes y convención de secretos |
| `scripts/deploy-production.sh` | Secuencia de despliegue que se extiende con el overlay |
| `scripts/backup-production.sh` | Backup que se amplía a `tanish_laravel` y storage |
| `docs/FINAL-ALIGNMENT-REPORT.md` | Brechas y alineación académica del estado actual |

### 13.1 Anexo A: glosario

| Término | Definición |
|---|---|
| SOA | Arquitectura orientada a servicios; servicios gruesos con contratos explícitos |
| Microservicio | Servicio fino, con base de datos y despliegue independientes; no es el caso de esta propuesta |
| Webhook | Notificación HTTP que un sistema envía a otro cuando ocurre un evento |
| HMAC | Código de autenticación de mensaje con clave; aquí SHA256 |
| Idempotencia | Propiedad por la cual repetir una operación no cambia el resultado |
| Kardex | Historial cronológico de movimientos de inventario |
| Snapshot | Copia de lectura de un dato de WooCommerce almacenada en Laravel |
| Reconciliación | Comparación periódica entre la copia de Laravel y la fuente WooCommerce |
| Dead-letter | Registro de mensajes que agotaron sus reintentos |
| HPOS | High-Performance Order Storage de WooCommerce; pedidos en tablas propias |
| Outbox | Patrón de persistencia de eventos; no se usa en esta fase porque el publicador es WooCommerce |
| Backoff | Espera creciente entre reintentos |
| Feature flag | Interruptor de configuración para habilitar o deshabilitar una funcionalidad |

### 13.2 Anexo B: decisiones que requieren ADR antes de implementar

1. Confirmar si el panel Laravel se publica en `admin.dominio.com` o solo en la red interna.
2. Confirmar si el webhook se entrega por red interna o por HTTPS público.
3. Confirmar la política de retención de payloads y datos personales.
4. Confirmar si se usan dos pares de claves de WooCommerce o uno solo con restricciones de código.
5. Confirmar el proveedor de WhatsApp y el cumplimiento de opt-in.
6. Confirmar si la Fase 5 se ejecuta o se declara fuera de alcance.
7. Confirmar si el historial legado se importa completo o solo los últimos 12 meses.
8. Confirmar el nivel de servicio y la ventana de mantenimiento.

---

## Cierre

La propuesta no compite con WordPress ni con WooCommerce. Los conserva como el centro del comercio y agrega un servicio Laravel que aporta administración, trazabilidad e integración. La decisión es deliberadamente conservadora: dos servicios, un contrato claro, sin transacciones distribuidas y sin reemplazar la plataforma exigida.

El resultado esperado es un sistema que cumple el requisito académico, conserva la evidencia existente y demuestra una evolución arquitectónica real: de un monolito WordPress funcional a una SOA de dos servicios con webhooks firmados, colas, idempotencia, kardex y reconciliación.
