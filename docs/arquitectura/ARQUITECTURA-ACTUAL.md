# Arquitectura actual de TANISH

| Campo | Valor |
|---|---|
| Documento | Arquitectura actual del sistema |
| Proyecto | TANISH S.A.C. — e-commerce de útiles escolares y de oficina |
| Rama de trabajo | `docs/arquitectura-laravel` |
| Fecha de corte | 2026-11-23 |
| Alcance | Estado implementado y verificable en el repositorio; no describe la propuesta de evolución |
| Fuente de datos operativos | `docs/sprints/SPRINT-00-BASELINE.md` (snapshot de runtime) |

Este documento describe **cómo funciona hoy** el sistema. No propone cambios, no reemplaza la propuesta de evolución y no modifica código. Las rutas citadas son relativas a la raíz del repositorio.

---

## 1. Resumen ejecutivo

TANISH es un **monolito modular** construido sobre **WordPress + WooCommerce**, con un tema propio (`themes/tanish-storefront`), un plugin de negocio (`plugins/tanish-inventory`) y varios mu-plugins para preocupaciones transversales. La aplicación se renderiza en el servidor con PHP (**SSR**), usa **una sola base de datos MySQL** y se despliega en **un único nodo** contenerizado con Docker Compose. En producción, el tráfico entra por un **proxy de borde en Cloudflare Workers** y llega al origen a través de **Caddy**, que actúa como proxy inverso y terminador TLS.

### 1.1 Clasificación formal

| Dimensión | Clasificación | Evidencia |
|---|---|---|
| Estilo arquitectónico | Monolito modular | Un solo despliegue WordPress; módulos separados por plugin, tema y mu-plugins |
| Organización interna | Arquitectura en capas | Presentación (tema), dominio/aplicación (WooCommerce + servicio de inventario), persistencia (capa `Database` + APIs de WordPress) |
| Renderizado | Server-Side Rendering (SSR) | Plantillas PHP y jerarquía de plantillas de WordPress; sin SPA ni frontend separado |
| Persistencia | Una base de datos relacional compartida | `docker-compose.prod.yml` define un único servicio `mysql` |
| Despliegue | Single-node contenerizado | Un contenedor WordPress, uno MySQL y uno Caddy en el mismo host |
| Borde | Proxy inverso en el edge | Worker `tanish-proxy` en Cloudflare y Caddy en el VPS |
| Integración interna | Llamadas en proceso y hooks de WordPress | `add_action` / `add_filter`; no hay bus de eventos ni colas propias |
| Autorización | Capabilities de WordPress | `manage_tanish_inventory`, roles `almacen` y `ventas` |
| Madurez | MVP en producción básica | Sin suite de pruebas automatizadas; CI limitado a lint y validación de Compose |

### 1.2 Datos operativos de referencia

| Dato | Valor | Fuente |
|---|---|---|
| Productos publicados | 12 | `docs/sprints/SPRINT-00-BASELINE.md` |
| Categorías | 15 (14 activas + `uncategorized`) | `docs/sprints/SPRINT-00-BASELINE.md` |
| Usuarios | 1 (`tanish_admin`) | `docs/sprints/SPRINT-00-BASELINE.md` |
| Páginas | 8 | `docs/sprints/SPRINT-00-BASELINE.md` |
| Movimientos de inventario al corte | 0 | `docs/sprints/SPRINT-00-BASELINE.md` |
| Tabla propia de inventario | `wp_tanish_inventory_movements` | `plugins/tanish-inventory/includes/class-tanish-inventory-database.php` |
| HPOS de WooCommerce | Activo | `docs/sprints/SPRINT-00-BASELINE.md` |

### 1.3 Trazabilidad de versiones

El baseline registra versiones de runtime. Al contrastarlas con el código del repositorio aparecen diferencias que deben tenerse presentes al leer este documento.

| Componente | Baseline (runtime) | Verificación en el repositorio | Observación |
|---|---|---|---|
| WordPress | 7.1 | No verificable sin ejecutar el contenedor | Dato tomado del snapshot de `SPRINT-00` |
| WooCommerce | 11.0.1 | `scripts/setup-production.sh` fija `WC_VERSION="11.1.0"` para traducciones | Posible desalineación entre runtime y aprovisionamiento |
| PHP | 8.3.33 | `docker-compose.prod.yml` usa `wordpress:php8.3-apache` | El parche exacto no está fijado en la imagen |
| Apache | 2.4.68 | Incluido en la imagen `php8.3-apache` | El parche exacto no está fijado |
| MySQL | 8.0.46 | `docker-compose.prod.yml` usa `mysql:8.0` | Etiqueta flotante de minor |
| TANISH Inventory | 0.3.0 | Header del plugin `0.3.0`; versión de esquema `0.4.0` | Las dos versiones son distintas por diseño |
| TANISH Storefront | 2.0.0 | `style.css` declara `Version: 3.0.0` | El baseline está desactualizado respecto del tema |
| Ubuntu | 24.04 LTS | No verificable desde el repositorio | Dato del host |
| Docker Engine | 29.1.3 | No verificable desde el repositorio | Dato del host |

---

## 2. Diagrama de flujo de una petición

### 2.1 Ruta principal de producción

```
                              INTERNET
                                 |
                                 | HTTPS
                                 v
        +--------------------------------------------------+
        | Cloudflare Workers                               |
        | Worker: tanish-proxy                             |
        | - Reescribe la URL al origen                     |
        | - Añade X-Forwarded-Host / -Proto / -Port        |
        | - Devuelve 502 si el origen no responde          |
        +--------------------------------------------------+
                                 |
                                 | HTTP
                                 | http://185.176.254.183.nip.io
                                 | (nip.io resuelve a la IP del VPS)
                                 v
        +--------------------------------------------------+
        | VPS Ubuntu 24.04 - Docker Compose                |
        |                                                  |
        |  +--------------------------------------------+  |
        |  | Caddy :80 / :443                           |  |
        |  | - Website block para CADDY_DOMAIN          |  |
        |  | - Bloque http://...nip.io para el Worker   |  |
        |  | - reverse_proxy wordpress:80               |  |
        |  +--------------------------------------------+  |
        |                       |                          |
        |                       v                          |
        |  +--------------------------------------------+  |
        |  | WordPress + Apache (php8.3-apache)         |  |
        |  | - index.php -> wp-blog-header.php          |  |
        |  | - wp-config.php lee variables de entorno   |  |
        |  | - mu-plugins -> plugins -> tema            |  |
        |  | - WooCommerce + TANISH Inventory           |  |
        |  +--------------------------------------------+  |
        |                       |                          |
        |                       v                          |
        |  +--------------------------------------------+  |
        |  | MySQL 8.0 (solo red interna)               |  |
        |  | - Tablas core de WordPress                 |  |
        |  | - Tablas de WooCommerce (HPOS)             |  |
        |  | - wp_tanish_inventory_movements            |  |
        |  +--------------------------------------------+  |
        +--------------------------------------------------+
                                 |
                                 | Respuesta HTTP
                                 v
        +--------------------------------------------------+
        | Worker tanish-proxy                              |
        | - Propaga status y body                          |
        | - Elimina la cabecera alt-svc                    |
        +--------------------------------------------------+
                                 |
                                 v
                              CLIENTE
```

### 2.2 Ruta alternativa de entrada

Existe un segundo Worker, `workers/tanish-mvp`, que funciona como entrada configurable mediante `ORIGIN_URL`. No es el mismo código que `tanish-proxy`: reescribe `Location` y `Set-Cookie`, añade `X-Forwarded-For` / `X-Real-IP` y devuelve páginas HTML de error 502 y 503. Su propósito es servir como proxy de entrada alternativo o de respaldo, no como CDN.

```
Cliente -> tanish-mvp (ORIGIN_URL) -> origen WordPress -> respuesta
                |
                +-> 503 si ORIGIN_URL falta o no es http(s)
                +-> 502 si el fetch al origen falla
```

### 2.3 Transformaciones relevantes en el borde

| Transformación | `tanish-proxy` | `tanish-mvp` |
|---|---|---|
| Reescribir URL al origen | Sí | Sí |
| `X-Forwarded-Host` | Sí | Sí |
| `X-Forwarded-Proto` | Fija `https` | Usa el protocolo entrante |
| `X-Forwarded-Port` | Fija `443` | No lo fija |
| `X-Forwarded-For` / `X-Real-IP` | No los sobrescribe | Los sobrescribe con la IP del cliente |
| Reescribir `Location` | No | Sí |
| Reescribir `Set-Cookie` | No | Sí |
| Página 502 | Texto plano | HTML |
| Página 503 por configuración | No | Sí |
| Eliminar `alt-svc` | Sí | No |

La diferencia de comportamiento es una decisión de implementación, pero también una fuente de deuda: dos proxies con lógicas distintas pueden producir resultados distintos ante el mismo origen.

---

## 3. Diagrama de capas

La aplicación no impone capas mediante namespaces ni interfaces formales, pero la separación es identificable por responsabilidades y dependencias.

```
+----------------------------------------------------------------------------------+
| CAPA DE PRESENTACION                                                             |
|                                                                                  |
|  Tema TANISH Storefront (SSR)          Panel administrativo de WordPress         |
|  - header.php / footer.php             - TANISH Inventory (admin)                |
|  - front-page.php / page-*.php         - Dashboard y branding (mu-plugin)        |
|  - woocommerce/single-product.php      - WooCommerce admin                       |
|  - ui/*, layout/*                      - Páginas de acceso y formularios         |
|  - style.css, assets/js/storefront.js  - Plantillas HTML de correo               |
+----------------------------------------------------------------------------------+
                                        |
                                        | llama APIs de WordPress/WooCommerce,
                                        | servicios del plugin y funciones del tema
                                        v
+----------------------------------------------------------------------------------+
| CAPA DE DOMINIO / APLICACION                                                     |
|                                                                                  |
|  WooCommerce core                        TANISH Inventory                         |
|  - Productos, variaciones, stock         - Tanish_Inventory_Service              |
|  - Carrito, checkout, pedidos            - Reglas de entrada/salida/ajuste       |
|  - Impuestos, envíos, cupones            - Validaciones y cálculo de stock       |
|  - Clientes y sesiones                   - Tanish_Inventory_Capabilities         |
|                                          - Tanish_Yape_Gateway                    |
|  WordPress core                          - Tanish_MercadoPago_Link_Gateway        |
|  - Posts, páginas, usuarios, términos    - Tanish_WhatsApp                       |
|  - Opciones, transients, nonces          - Tanish_CPTs (banners y videos)        |
+----------------------------------------------------------------------------------+
                                        |
                                        v
+----------------------------------------------------------------------------------+
| CAPA DE SERVICIOS DE APLICACION                                                  |
|                                                                                  |
|  - Tanish_Inventory_Admin: controlador de formularios y pantallas                |
|  - Formularios de Contacto y Libro de Reclamaciones (plantillas de página)       |
|  - tanish_save_form_entry(): persistencia de envíos                              |
|  - tanish_send_html_mail(): correo HTML                                          |
|  - Dashboard administrativo: estadísticas y series temporales                    |
|  - Protección anti-spam y rate limiting por transients                           |
+----------------------------------------------------------------------------------+
                                        |
                                        v
+----------------------------------------------------------------------------------+
| CAPA DE PERSISTENCIA                                                             |
|                                                                                  |
|  - Tanish_Inventory_Database: esquema, dbDelta, consultas y escritura            |
|  - $wpdb: SQL preparado sobre tablas core y tabla propia                         |
|  - APIs de WordPress: posts, postmeta, options, users, terms, transients          |
|  - Data stores de WooCommerce: productos, pedidos, stock, HPOS                   |
|  - Tabla propia: wp_tanish_inventory_movements                                   |
+----------------------------------------------------------------------------------+
                                        ^
                                        | atraviesa todas las capas
+----------------------------------------------------------------------------------+
| CAPA TRANSVERSAL (mu-plugins)                                                    |
|                                                                                  |
|  - tanish-security.php: XML-RPC desactivado y cabeceras de seguridad             |
|  - tanish-forms.php: CPT de reclamos/contacto e IP real                          |
|  - tanish-cron.php: ejecución de cron en desarrollo sin loopback                 |
|  - tanish-admin-branding.php: identidad visual, dashboard y navegación           |
|  - brevo-smtp.php: configuración SMTP de PHPMailer                               |
|  - Nonces, sanitización, capabilities y logging en error_log                     |
+----------------------------------------------------------------------------------+
                                        ^
                                        | soporte de ejecución
+----------------------------------------------------------------------------------+
| CAPA DE PLATAFORMA                                                               |
|                                                                                  |
|  - Docker Compose (desarrollo y producción)                                      |
|  - Caddy como proxy inverso y TLS                                                |
|  - Cloudflare Workers como proxy de borde                                        |
|  - MySQL 8.0, Apache 2.4 y PHP 8.3                                               |
|  - Volúmenes Docker, backups y GitHub Actions                                    |
+----------------------------------------------------------------------------------+
```

### 3.1 Reglas de dependencia observadas

| Regla | Cumplimiento | Evidencia |
|---|---|---|
| La presentación no ejecuta SQL directo | Cumplimiento parcial: el tema usa APIs de WordPress/WooCommerce; el admin del plugin usa el servicio y la capa de base de datos | `themes/tanish-storefront/functions.php`, `class-tanish-inventory-admin.php` |
| El dominio no conoce HTTP | Cumplimiento parcial: el servicio devuelve `WP_Error` o arrays, pero depende de APIs globales de WooCommerce | `class-tanish-inventory-service.php` |
| La persistencia está encapsulada | Cumplimiento parcial: existe `Tanish_Inventory_Database`, pero también hay consultas directas en el mu-plugin de branding y en el tema | `class-tanish-inventory-database.php`, `tanish-admin-branding.php` |
| Las preocupaciones transversales están fuera del dominio | Cumplimiento alto: seguridad, SMTP, cron, branding y formularios viven en mu-plugins | `mu-plugins/` |
| La configuración sensible viene del entorno | Cumplimiento parcial: MySQL y SMTP sí; sales y claves de WordPress tienen valores por defecto en `wp-config.php` | `wp-config.php` |

---

## 4. Inventario de componentes

### 4.1 Infraestructura y borde

| Componente | Ubicación | Responsabilidad | Tecnología | Líneas aprox. |
|---|---|---|---|---|
| Compose de desarrollo | `docker-compose.yml` | MySQL, WordPress y Mailpit en red local | Docker Compose | 82 |
| Compose de producción | `docker-compose.prod.yml` | MySQL, WordPress y Caddy con volúmenes persistentes | Docker Compose | 104 |
| Configuración de Caddy | `caddy/Caddyfile` | TLS, gzip y proxy inverso a `wordpress:80` | Caddy 2 | 22 |
| Worker proxy principal | `workers/tanish-proxy/src/index.js` | Proxy inverso hacia el VPS; cabeceras `X-Forwarded-*`; 502 | JavaScript para Workers | 47 |
| Configuración del proxy principal | `workers/tanish-proxy/wrangler.toml` | Nombre, entrypoint y `ORIGIN` | Wrangler / TOML | 13 |
| Worker de entrada alternativo | `workers/tanish-mvp/src/index.ts` | Proxy con reescritura de cookies y redirects; páginas 502/503 | TypeScript para Workers | 250 |
| Configuración del Worker alternativo | `workers/tanish-mvp/wrangler.jsonc` | Entornos `staging` y `production`; `ORIGIN_URL` vacía | Wrangler / JSONC | 26 |
| Documentación del Worker alternativo | `workers/tanish-mvp/README.md` | Contrato de `ORIGIN_URL` y comportamiento de error | Markdown | 71 |
| CI | `.github/workflows/ci.yml` | Lint PHP, validación de Compose y ShellCheck | GitHub Actions | 39 |
| Despliegue continuo | `.github/workflows/deploy.yml` | Validación y despliegue por SSH al VPS | GitHub Actions | 56 |
| Backup programado | `.github/workflows/backup.yml` | Backup semanal del VPS y artefacto de GitHub | GitHub Actions | 48 |

### 4.2 Plugin TANISH Inventory

| Componente | Ubicación | Responsabilidad | Tecnología | Líneas aprox. |
|---|---|---|---|---|
| Bootstrap del plugin | `plugins/tanish-inventory/tanish-inventory.php` | Constantes, carga de archivos, activación, arranque en `plugins_loaded` | PHP / WordPress | 95 |
| Capa de base de datos | `includes/class-tanish-inventory-database.php` | Esquema con `dbDelta`, inserción y consultas de movimientos, kardex y serie diaria | PHP / `$wpdb` | 183 |
| Servicio de inventario | `includes/class-tanish-inventory-service.php` | Reglas de entrada, salida y ajuste; validaciones; actualización de stock; registro del movimiento | PHP / WooCommerce | 149 |
| Controlador de administración | `includes/class-tanish-inventory-admin.php` | Menús, formularios, nonces, dashboard, movimientos y kardex | PHP / WordPress admin | 662 |
| Capabilities y roles | `includes/class-tanish-inventory-capabilities.php` | Crea y sincroniza `manage_tanish_inventory`, roles `almacen` y `ventas`; acceso al admin | PHP | 104 |
| Custom Post Types | `includes/class-tanish-cpts.php` | Banners y videos con meta boxes y columnas de administración | PHP | 246 |
| Integración WhatsApp | `includes/class-tanish-whatsapp.php` | Opción del número y pantalla de configuración | PHP / Settings API | 134 |
| Pasarela Yape simulada | `includes/class-tanish-yape-gateway.php` | Pedido en espera, reducción de stock y confirmación manual | PHP / `WC_Payment_Gateway` | 212 |
| Pasarela Mercado Pago por link | `includes/class-tanish-mercadopago-link.php` | Redirección a link externo y confirmación manual | PHP / `WC_Payment_Gateway` | 202 |

### 4.3 mu-plugins

| Componente | Ubicación | Responsabilidad | Tecnología | Líneas aprox. |
|---|---|---|---|---|
| Endurecimiento básico | `mu-plugins/tanish-security.php` | Desactiva XML-RPC y añade `X-Content-Type-Options: nosniff` | PHP | 17 |
| Formularios | `mu-plugins/tanish-forms.php` | CPT `tanish_reclamo` y `tanish_contacto`, IP real y guardado de envíos | PHP | 210 |
| Cron de desarrollo | `mu-plugins/tanish-cron.php` | Ejecuta eventos vencidos y Action Scheduler dentro de la petición cuando `DISABLE_WP_CRON` está activo | PHP | 65 |
| Branding administrativo | `mu-plugins/tanish-admin-branding.php` | Login, barra superior, menú, migas, dashboard y estadísticas | PHP | 1407 |
| CSS del branding | `mu-plugins/tanish-admin/assets/admin-branding.css` | Estilos del panel | CSS | 1186 |
| CSS del login | `mu-plugins/tanish-admin/assets/login-branding.css` | Estilos del login | CSS | 262 |
| SMTP | `mu-plugins/brevo-smtp.php` | Configura PHPMailer con variables `SMTP_*`; remitente y logging | PHP | 130 |

### 4.4 Tema TANISH Storefront

| Componente | Ubicación | Responsabilidad | Tecnología | Líneas aprox. |
|---|---|---|---|---|
| Configuración del tema | `themes/tanish-storefront/functions.php` | Soporte de WooCommerce, menús, assets, traducciones, WhatsApp, GTM/GA4, formularios y toast | PHP | 872 |
| Hoja de estilos principal | `themes/tanish-storefront/style.css` | Identidad visual y layout responsive | CSS | 3848 |
| JavaScript del storefront | `themes/tanish-storefront/assets/js/storefront.js` | Interacciones de navegación, carrito y UI | JavaScript | 414 |
| Cabecera | `header.php` | Logo, búsqueda, carrito, sesión y navegación | PHP | 75 |
| Pie de página | `footer.php` | Footer principal, subfooter y dock de WhatsApp | PHP | 18 |
| Portada | `front-page.php` | Composición de secciones de la home | PHP | 19 |
| Página genérica | `page.php` | Render de páginas con hero y contenido | PHP | 29 |
| Fallback | `index.php` | Listado genérico | PHP | 35 |
| 404 | `404.php` | Página de error | PHP | 199 |
| Archivo de productos | `archive-product.php` | Catálogo con sidebar de filtros y `woocommerce_content()` | PHP | 73 |
| Producto individual | `woocommerce/single-product.php` | Layout de dos columnas y hooks de WooCommerce | PHP | 76 |
| Conteo de resultados | `woocommerce/loop/result-count.php` | Texto en español del conteo | PHP | 21 |
| Página de acceso | `page-acceso.php` | Login con `wp_signon()`, rate limit y redirección por rol | PHP | 216 |
| Página de contacto | `page-contacto.php` | Validación, anti-spam, guardado y correo | PHP | 206 |
| Libro de Reclamaciones | `page-libro-de-reclamaciones.php` | Formulario INDECOPI, correlativo, guardado y dos correos | PHP | 344 |
| Página Nosotros | `page-nosotros.php` | Contenido institucional | PHP | 121 |
| Layouts | `layout/` | Navbar, footer, hero, sidebar y dock de WhatsApp | PHP | 183 en total |
| Componentes UI | `ui/` | Hero, categorías, productos, promo, beneficios, videos y CTA | PHP | 415 en total |
| Includes del tema | `inc/` | Carrito lateral, plantilla de correo y performance | PHP | 382 en total |

### 4.5 Scripts operativos

| Componente | Ubicación | Responsabilidad | Tecnología | Líneas aprox. |
|---|---|---|---|---|
| Backup local | `scripts/backup.sh` | `mysqldump` comprimido del contenedor `tanish-mysql` | Bash | 41 |
| Backup de producción | `scripts/backup-production.sh` | MySQL, uploads y configuración/código custom; excluye secretos | Bash | 53 |
| Despliegue | `scripts/deploy-production.sh` | Valida Compose, hace `git pull --ff-only`, levanta servicios y verifica salud | Bash | 38 |
| Restauración | `scripts/restore.sh` | Detiene WordPress, recrea la base e importa un dump | Bash | 81 |
| Aprovisionamiento de producción | `scripts/setup-production.sh` | Instala plugin oficial de Mercado Pago, traducciones y permisos | Bash | 140 |

---

## 5. Modelo de datos

### 5.1 Tablas core de WordPress y WooCommerce utilizadas

El prefijo es configurable mediante `WORDPRESS_TABLE_PREFIX` y por defecto es `wp_` (`wp-config.php`). Las tablas de WooCommerce las administra el plugin; el repositorio no las crea.

| Tabla | Uso en TANISH | Evidencia |
|---|---|---|
| `wp_posts` | Productos, páginas, banners, videos, adjuntos y entradas de formularios | `wp_insert_post()` en `tanish-forms.php`; CPT en `class-tanish-cpts.php` |
| `wp_postmeta` | Meta de productos (`_stock`, `_manage_stock`, `_stock_status`), banners, videos y entradas de formularios | `tanish-admin-branding.php`, `class-tanish-cpts.php`, `tanish-forms.php` |
| `wp_options` | Configuración del sitio, plugins activos, versiones de esquema, WhatsApp y contadores de reclamos | `get_option()` / `update_option()` en plugin y mu-plugins |
| `wp_users` | Usuario administrador y personal | `docs/sprints/SPRINT-00-BASELINE.md` |
| `wp_usermeta` | Roles y capabilities | `class-tanish-inventory-capabilities.php` |
| `wp_terms` | Categorías de producto y términos | `ui/categories.php`, `archive-product.php` |
| `wp_term_taxonomy` | Taxonomía `product_cat` y conteos | `archive-product.php` |
| `wp_term_relationships` | Relación producto-categoría | Modelo estándar de WordPress |
| `wp_termmeta` | Miniatura de categoría (`thumbnail_id`) | `ui/categories.php` |
| `wp_tanish_inventory_movements` | Ledger de movimientos manuales de inventario | `class-tanish-inventory-database.php` |
| `wp_wc_orders` | Pedidos con HPOS activo | `docs/sprints/SPRINT-00-BASELINE.md` |
| `wp_wc_orders_meta` | Meta de pedidos con HPOS | WooCommerce HPOS |
| `wp_wc_order_addresses` | Direcciones de pedido con HPOS | WooCommerce HPOS |
| `wp_wc_order_operational_data` | Datos operativos del pedido con HPOS | WooCommerce HPOS |
| `wp_wc_order_stats` | Estadísticas de pedidos para reportes | WooCommerce Analytics |
| `wp_woocommerce_order_items` | Líneas de pedido | WooCommerce |
| `wp_woocommerce_order_itemmeta` | Meta de líneas de pedido | WooCommerce |
| `wp_wc_product_meta_lookup` | Índice de búsqueda y filtros de producto | WooCommerce |
| `wp_actionscheduler_actions` | Acciones programadas de WooCommerce | `tanish-cron.php` invoca el runner |
| `wp_actionscheduler_logs` | Logs de Action Scheduler | WooCommerce |

> Nota: con HPOS activo, WooCommerce mantiene los pedidos en `wp_wc_orders` y tablas asociadas, mientras las líneas de pedido siguen en `wp_woocommerce_order_items` / `wp_woocommerce_order_itemmeta`. El repositorio no define ese esquema; se documenta porque forma parte del modelo de datos real.

### 5.2 Esquema exacto de `wp_tanish_inventory_movements`

El esquema se crea con `dbDelta()` en `Tanish_Inventory_Database::create_table()` (`plugins/tanish-inventory/includes/class-tanish-inventory-database.php`, líneas 25-39). La siguiente es una normalización legible del SQL; los nombres, tipos, defaults e índices son los del código.

```sql
CREATE TABLE wp_tanish_inventory_movements (
  id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id      bigint(20) unsigned NOT NULL,
  movement_type   varchar(32) NOT NULL,
  quantity_change decimal(12,2) NOT NULL,
  stock_before    decimal(12,2) NOT NULL,
  stock_after     decimal(12,2) NOT NULL,
  reason          text NOT NULL,
  user_id         bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY product_id (product_id),
  KEY movement_type (movement_type),
  KEY created_at (created_at)
) /* $wpdb->get_charset_collate() */;
```

| Columna | Tipo | Semántica | Observación |
|---|---|---|---|
| `id` | `bigint unsigned` | Identificador del movimiento | Auto incremental |
| `product_id` | `bigint unsigned` | ID del producto en `wp_posts` | Relación lógica, sin foreign key |
| `movement_type` | `varchar(32)` | `entry`, `exit` o `adjustment` | Validado en el servicio, no por constraint de base de datos |
| `quantity_change` | `decimal(12,2)` | Diferencia firmada entre stock posterior y anterior | En entrada es positiva; en salida, negativa; en ajuste, la diferencia |
| `stock_before` | `decimal(12,2)` | Stock antes del movimiento | Se lee de WooCommerce |
| `stock_after` | `decimal(12,2)` | Stock después del movimiento | Se escribe en WooCommerce |
| `reason` | `text` | Motivo obligatorio | Se guarda con `sanitize_textarea_field()` |
| `user_id` | `bigint unsigned` | Usuario que ejecuta el movimiento | `0` cuando es el sistema |
| `created_at` | `datetime` | Fecha del movimiento | El insert envía `current_time('mysql')`; el default es `CURRENT_TIMESTAMP` |

Índices: `PRIMARY KEY (id)`, `KEY product_id`, `KEY movement_type`, `KEY created_at`. No hay foreign keys, unique constraints ni checks de dominio.

### 5.3 Relaciones lógicas

```
wp_users (1) ----< wp_usermeta
    |
    | user_id (lógica, sin FK)
    v
wp_tanish_inventory_movements >---- product_id ---- (lógica) ----> wp_posts (product)
    |                                                                  |
    |                                                                  +----< wp_postmeta
    |                                                                  +----< wp_term_relationships >---- wp_term_taxonomy >---- wp_terms
    |
    +---- created_at, movement_type, quantity_change, stock_before, stock_after

wp_wc_orders (HPOS) ----< wp_wc_orders_meta
    |                 +----< wp_wc_order_addresses
    |                 +----< wp_wc_order_operational_data
    |                 +----< wp_wc_order_stats
    +----< wp_woocommerce_order_items ----< wp_woocommerce_order_itemmeta
```

### 5.4 Observaciones del modelo

1. **WooCommerce es la fuente de verdad del stock.** El servicio lee `get_stock_quantity()` y escribe con `set_stock_quantity()` / `save()` antes de insertar el movimiento (`class-tanish-inventory-service.php`, líneas 99-121).
2. **El ledger es secundario y parcial.** Solo el servicio de inventario inserta en `wp_tanish_inventory_movements`. Las reducciones de stock originadas por pedidos usan `wc_reduce_stock_levels()` o `reduce_stock_levels()` en las pasarelas y no escriben en ese ledger. Por tanto, el kardex no refleja automáticamente las ventas.
3. **No hay transacción única.** La actualización de stock y el insert del movimiento son dos operaciones separadas; si el insert falla, el servicio revierte el stock de forma compensatoria (`class-tanish-inventory-service.php`, líneas 134-140). No se observa bloqueo pesimista ni transacción de base de datos.
4. **No hay integridad referencial.** `product_id` y `user_id` no tienen foreign keys; un producto o usuario eliminado deja registros huérfanos. Las consultas resuelven nombres con `LEFT JOIN`, por lo que el nombre puede quedar vacío.
5. **El tipo de movimiento no está restringido en la base.** La columna es `varchar(32)`; la validación `entry|exit|adjustment` vive en el servicio (`class-tanish-inventory-service.php`, líneas 77-79).
6. **La fecha se escribe desde PHP.** Aunque el esquema tiene default, el insert envía `current_time('mysql')`, lo que alinea la fecha con la zona horaria del sitio.

### 5.5 Volumen de datos de referencia

| Entidad | Registros | Comentario |
|---|---|---|
| Productos | 12 | Catálogo pequeño, apto para consultas sin paginación agresiva |
| Categorías | 15 | Incluye `uncategorized` |
| Usuarios | 1 | Solo administrador en el snapshot |
| Páginas | 8 | Inicio, Tienda, Cart, Checkout, My Account, Nosotros, Contacto, Libro de Reclamaciones |
| Movimientos | 0 | Al momento del baseline |

El plugin define roles `almacen` y `ventas` y los sincroniza en `init` y `admin_init` (`class-tanish-inventory-capabilities.php`, líneas 42-74), aunque el baseline no reporta usuarios asignados a esos roles.

---

## 6. Ciclo de vida de una petición

### 6.1 Secuencia de arranque de WordPress

```
Petición HTTP
   |
   v
Apache + PHP
   |
   v
index.php -> wp-blog-header.php -> wp-load.php -> wp-config.php
   |
   +-> Lee WORDPRESS_DB_* y WORDPRESS_CONFIG_EXTRA del entorno
   +-> Define ABSPATH, prefijo y constantes de depuración
   |
   v
wp-settings.php
   |
   +-> Carga mu-plugins (automático, sin activación)
   |      tanish-security.php
   |      tanish-forms.php
   |      tanish-cron.php
   |      tanish-admin-branding.php
   |      brevo-smtp.php
   |
   +-> Carga plugins activos
   |      WooCommerce
   |      TANISH Inventory
   |
   +-> do_action('plugins_loaded')
   |      tanish_inventory_bootstrap()
   |        - Verifica class_exists('WooCommerce')
   |        - Carga includes
   |        - maybe_upgrade() de la tabla
   |        - Instancia capabilities, servicio, admin, WhatsApp y CPTs
   |
   +-> do_action('after_setup_theme')
   |      tanish_storefront_setup()
   |
   +-> do_action('init')
   |      - Registra CPTs de banners/videos
   |      - Registra CPTs de reclamos/contacto
   |      - Asegura capabilities y roles
   |      - Asegura permalinks
   |
   +-> do_action('wp_loaded')
   |      - En desarrollo, ejecuta cron interno si DISABLE_WP_CRON
   |
   v
Parseo de la petición y selección de plantilla
   |
   +-> front-page.php
   +-> archive-product.php
   +-> woocommerce/single-product.php
   +-> page-{slug}.php
   +-> page.php
   +-> index.php
   +-> 404.php
   |
   v
Render con hooks (wp_head, woocommerce_*, wp_footer)
   |
   v
Respuesta HTML
```

### 6.2 Hooks principales

| Hook | Archivo | Efecto |
|---|---|---|
| `plugins_loaded` | `tanish-inventory.php` | Arranca el plugin solo si WooCommerce está activo; si no, muestra aviso |
| `after_setup_theme` | `functions.php` | Soporte de WooCommerce, miniaturas, menús y galería |
| `init` | `class-tanish-cpts.php` | Registra `tanish_banner` y `tanish_video` |
| `init` | `class-tanish-inventory-capabilities.php` | Crea/sincroniza roles y capabilities |
| `init` | `functions.php` | Fuerza permalinks `/%postname%/` si están vacíos |
| `init` | `tanish-forms.php` | Registra `tanish_reclamo` y `tanish_contacto` |
| `wp_loaded` | `tanish-cron.php` | Ejecuta eventos vencidos en desarrollo |
| `admin_menu` | `class-tanish-inventory-admin.php` | Registra el menú TANISH Inventory y sus subpáginas |
| `admin_init` | `class-tanish-inventory-admin.php` | Procesa los POST de entrada, salida y ajuste |
| `admin_init` | `class-tanish-whatsapp.php` | Registra la opción del número de WhatsApp |
| `wp` | `functions.php` | Añade el botón de WhatsApp en producto individual |
| `wp` | `class-tanish-mercadopago-link.php` | Fuerza la instancia del gateway en cuenta y pedido recibido |
| `wp_head` | `functions.php` | Inserta GTM y preloads |
| `wp_footer` | `functions.php` | Inserta GA4 y el toast de confirmación |
| `woocommerce_before_main_content` | `functions.php` | Quita la sidebar en producto individual |
| `woocommerce_single_product_summary` | `functions.php` | Añade el botón “Comprar por WhatsApp” |
| `woocommerce_add_to_cart_fragments` | `inc/cart-drawer.php` | Refresca contador y drawer por AJAX |
| `woocommerce_payment_gateways` | `class-tanish-yape-gateway.php`, `class-tanish-mercadopago-link.php` | Registra las pasarelas |
| `woocommerce_thankyou` | `functions.php` | Envía el evento `purchase` a GA4 una sola vez |
| `phpmailer_init` | `brevo-smtp.php` | Configura SMTP |
| `wp_mail_failed` | `brevo-smtp.php` | Registra fallos de correo |

### 6.3 Selección de plantilla

| Tipo de petición | Plantilla | Evidencia |
|---|---|---|
| Portada | `front-page.php` | `front-page.php` |
| Catálogo de tienda | `archive-product.php` | `archive-product.php` |
| Producto individual | `woocommerce/single-product.php` | Override del template de WooCommerce |
| Página “Contacto” | `page-contacto.php` | `Template Name: Contacto` |
| Página “Libro de Reclamaciones” | `page-libro-de-reclamaciones.php` | `Template Name: Libro de Reclamaciones` |
| Página “Acceso” | `page-acceso.php` | `Template Name: Acceso` |
| Página “Nosotros” | `page-nosotros.php` | Plantilla específica |
| Página genérica | `page.php` | Fallback de páginas |
| Archivo genérico | `index.php` | Fallback del tema |
| Error 404 | `404.php` | Plantilla de error |

### 6.4 Petición de lectura: catálogo o producto

1. El Worker reescribe la URL y Caddy la envía a `wordpress:80`.
2. WordPress resuelve la consulta principal con `WP_Query`.
3. La plantilla llama a APIs de WooCommerce: `wc_get_products()`, `wc_get_product()`, `get_terms()`, `WC()->cart`.
4. Se leen opciones y transients (`get_option`, `get_transient`) y meta de producto/término.
5. Se renderiza HTML con hooks; `wp_footer` añade GA4 y el toast si corresponde.
6. La respuesta vuelve por Caddy y el Worker.

No hay caché de página ni de objetos en la infraestructura del repositorio. Caddy solo aplica `encode gzip`; los transients se almacenan en la base de datos.

### 6.5 Petición de escritura: movimiento de inventario

```
Admin autenticado
   |
   | POST admin.php?page=tanish-inventory-entry
   | nonce tanish_inventory_entry
   v
admin_init -> Tanish_Inventory_Admin::handle_form_submission()
   |
   +-> is_admin()
   +-> sanitize_key($_POST['tanish_inventory_action'])
   +-> current_user_can('manage_tanish_inventory')
   +-> check_admin_referer('tanish_inventory_entry')
   +-> Valida product_id, reason y quantity
   +-> wc_get_product($product_id)
   |
   v
Tanish_Inventory_Service::apply_movement()
   |
   +-> Valida tipo entry|exit|adjustment
   +-> Valida cantidad y motivo
   +-> Verifica manage_stock
   +-> Calcula stock_before y stock_after
   +-> Rechaza stock negativo o salida mayor al disponible
   +-> $product->set_stock_quantity($stock_after)
   +-> $product->save()
   +-> Tanish_Inventory_Database::insert_movement()
   |
   +-> Si el insert devuelve 0: revierte el stock y devuelve WP_Error
   |
   v
wp_safe_redirect(admin.php?page=...&status=success)
```

La operación no está envuelta en una transacción de base de datos. La consistencia se busca con una compensación: si falla el registro del movimiento, se restaura el stock anterior.

### 6.6 Petición de escritura: formulario público

```
Visitante
   |
   | POST /contacto/ o /libro-de-reclamaciones/
   | nonce del formulario
   v
Plantilla page-contacto.php / page-libro-de-reclamaciones.php
   |
   +-> wp_verify_nonce()
   +-> sanitize_text_field / sanitize_email / sanitize_textarea_field
   +-> Validación de campos obligatorios
   +-> Rate limit por IP y duplicados con transients
   |
   v
tanish_save_form_entry()
   |
   +-> wp_insert_post() en tanish_contacto o tanish_reclamo
   +-> update_post_meta('_tanish_fields')
   +-> update_post_meta('_tanish_ip')
   |
   v
tanish_send_html_mail()
   |
   +-> wp_mail() con plantilla HTML
   |
   +-> Si el correo sale: redirect 303 con toast (PRG)
   +-> Si el correo falla: se conserva el registro y se muestra error
```

### 6.7 Petición de checkout

1. WooCommerce gestiona carrito, checkout, cupones, impuestos y envío.
2. El cliente elige una pasarela registrada:
   - `Tanish_Yape_Gateway`: simulada, deja el pedido en `on-hold`, reduce stock y vacía el carrito.
   - `Tanish_MercadoPago_Link_Gateway`: guarda el link en el pedido, deja `on-hold`, reduce stock y vacía el carrito.
3. El pedido queda pendiente de confirmación manual. En Yape, un usuario con `manage_woocommerce` puede marcarlo como pagado desde la página de gracias o el detalle del pedido; en Mercado Pago, el link se muestra al cliente.
4. No hay webhook de confirmación automática en el código propio. El plugin oficial de Mercado Pago se instala aparte mediante `scripts/setup-production.sh`.

---

## 7. Infraestructura y despliegue

### 7.1 Desarrollo vs producción

| Aspecto | Desarrollo (`docker-compose.yml`) | Producción (`docker-compose.prod.yml`) |
|---|---|---|
| Servicios | `mysql`, `wordpress`, `mailpit` | `mysql`, `wordpress`, `caddy` |
| Imagen de WordPress | `wordpress:latest` | `wordpress:php8.3-apache` |
| Base de datos | `mysql:8.0` | `mysql:8.0` |
| Puerto de MySQL | Publicado en el host vía `MYSQL_PORT` | Solo `expose: 3306` en la red interna |
| Puerto web | WordPress publicado en `WORDPRESS_PORT` | WordPress solo `expose: 80`; Caddy publica 80/443 |
| TLS | No | Caddy con `CADDY_DOMAIN` y `CADDY_EMAIL` |
| Correo | Mailpit (`mailpit:1025`, UI en 8025) | Brevo (`smtp-relay.brevo.com:587`) |
| Código custom | Montado en lectura/escritura | Montado en solo lectura (`:ro`) |
| Volúmenes | `mysql_data`, `wordpress_data` | `tanish_mysql_data_prod`, `tanish_wordpress_data_prod`, `tanish_caddy_data`, `tanish_caddy_config` |
| Healthchecks | Solo MySQL | MySQL y WordPress |
| Dependencias | WordPress espera MySQL sano | WordPress espera MySQL sano; Caddy espera WordPress sano |
| Cron | `DISABLE_WP_CRON=true`; `tanish-cron.php` ejecuta en la petición | Cron real del servidor |
| Reinicio | `unless-stopped` | `unless-stopped` |
| Variables | `.env` | `.env.production` |
| Despliegue | `docker compose up -d` | `scripts/deploy-production.sh` o GitHub Actions |

### 7.2 Configuración de Caddy

`caddy/Caddyfile` contiene:

- Bloque global con `email {$CADDY_EMAIL}` y `auto_https disable_redirects`.
- Sitio `{$CADDY_DOMAIN}` con `encode gzip` y `reverse_proxy wordpress:80`.
- Sitio `http://185.176.254.183.nip.io` para la ruta del Worker, con `reverse_proxy wordpress:80` y `header_up Host tanish-proxy.solvegrades.workers.dev`.
- Fallback `:80` que responde 404.

Consecuencias:

- El dominio principal puede obtener TLS automático si el DNS apunta al VPS y los puertos están abiertos.
- La ruta del Worker usa HTTP en el origen; por eso WordPress marca `$_SERVER['HTTPS'] = 'on'` en producción (`docker-compose.prod.yml`, líneas 46-54) para evitar bucles de redirección.
- El bloque `nip.io` y el `Host` del Worker están codificados de forma literal. Cambiar de VPS o de hostname del Worker exige editar el Caddyfile.
- Caddy no aplica autenticación, allowlist de IPs de Cloudflare ni un secreto compartido. El origen es alcanzable por HTTP si se conoce el hostname `nip.io`.

### 7.3 Cloudflare Workers

| Aspecto | `tanish-proxy` | `tanish-mvp` |
|---|---|---|
| Archivo de entrada | `src/index.js` | `src/index.ts` |
| Configuración | `wrangler.toml` | `wrangler.jsonc` |
| Origen | `ORIGIN = "http://185.176.254.183.nip.io"` | `ORIGIN_URL` vacía por defecto |
| Entornos | No declara entornos | `staging` y `production` |
| Error de origen | 502 en texto plano | 502 en HTML |
| Error de configuración | No aplica: el origen está fijo | 503 en HTML si `ORIGIN_URL` falta o es inválida |
| Cookies | No las reescribe | Reescribe `Domain` y elimina `Domain` en localhost/IP |
| Redirects | No reescribe `Location` | Reescribe `Location` al host público |
| Cabecera `alt-svc` | La elimina | No la elimina |

El Worker `tanish-mvp` documenta explícitamente que `ORIGIN_URL` es obligatoria y que no sirve una landing falsa (`workers/tanish-mvp/README.md`, líneas 15-43). El Worker `tanish-proxy` no tiene esa validación: si `env.ORIGIN` faltara, la construcción de `new URL(env.ORIGIN)` fallaría antes del `try`.

### 7.4 Persistencia y volúmenes

| Volumen | Contenido | Ciclo de vida |
|---|---|---|
| `tanish_mysql_data_prod` | Datos de MySQL | Persistente; sobrevive a reinicios y recreación de contenedores |
| `tanish_wordpress_data_prod` | Núcleo de WordPress, uploads y contenido generado | Persistente; el código custom se monta encima desde el repositorio |
| `tanish_caddy_data` | Certificados y estado de Caddy | Persistente |
| `tanish_caddy_config` | Configuración autogestionada de Caddy | Persistente |

`wp-content/uploads` vive dentro de `wordpress_data`. Los backups de producción lo exportan desde el volumen. El código custom (`plugins`, `themes`, `mu-plugins`) no vive en el volumen: se monta desde el repositorio en solo lectura.

### 7.5 Backups y restauración

| Mecanismo | Alcance | Evidencia |
|---|---|---|
| `scripts/backup.sh` | Solo base de datos local; contenedor `tanish-mysql`; archivo `.sql.gz` | `scripts/backup.sh` |
| `scripts/backup-production.sh` | MySQL, `wp-content/uploads`, Compose, ejemplo de entorno, plugin, tema y Caddyfile | `scripts/backup-production.sh` |
| GitHub Actions `backup.yml` | Ejecuta el backup en el VPS y publica un artefacto con retención de 90 días | `.github/workflows/backup.yml` |
| `scripts/restore.sh` | Restauración local: detiene WordPress, recrea la base e importa | `scripts/restore.sh` |

El backup de producción **no incluye** `.env.production`; los secretos deben conservarse en un gestor externo. El script de restauración está orientado al entorno local (`docker-compose.yml` y contenedor `tanish-mysql`); no hay un script equivalente para producción en el repositorio.

### 7.6 Healthchecks y reinicio

| Servicio | Healthcheck | Dependencia |
|---|---|---|
| MySQL producción | `mysqladmin ping` cada 10 s, 10 reintentos, `start_period` 30 s | Ninguna |
| WordPress producción | `curl` o `wget` a `http://127.0.0.1/` cada 15 s, 10 reintentos, `start_period` 45 s | MySQL sano |
| Caddy producción | No define healthcheck | WordPress sano |
| MySQL desarrollo | `mysqladmin ping` cada 10 s, 5 reintentos | Ninguna |
| WordPress desarrollo | No define healthcheck | MySQL sano |
| Mailpit | No define healthcheck | Ninguna |

Todos los servicios usan `restart: unless-stopped`.

### 7.7 CI/CD

| Workflow | Disparador | Acciones |
|---|---|---|
| `ci.yml` | Push a `main`/`develop`, PR a `main` | Valida `docker-compose.prod.yml`, ejecuta `php -l` sobre plugins/temas/mu-plugins y ShellCheck sobre `scripts/*.sh` |
| `deploy.yml` | Push a `main` o ejecución manual | Valida lint y Compose; sube `deploy-production.sh` por SCP; ejecuta el deploy por SSH |
| `backup.yml` | Domingo 07:00 UTC o ejecución manual | Ejecuta `backup-production.sh` en el VPS, descarga los `.gz` y los publica como artefacto |

No hay ejecución de pruebas unitarias, de integración ni de aceptación en CI.

### 7.8 Topología de red

```
Internet
  |
  +-- HTTPS --> Cloudflare Workers (tanish-proxy / tanish-mvp)
  |                   |
  |                   +-- HTTP --> VPS:80 (Caddy, bloque nip.io)
  |
  +-- HTTPS --> VPS:443 (Caddy, dominio principal, si el DNS apunta al VPS)
                      |
                      +-- red Docker tanish-network
                              |
                              +-- wordpress:80 (Apache/PHP)
                              +-- mysql:3306 (solo interno)
```

La red `tanish-network` es un bridge de Docker. En producción, MySQL no se publica al host. WordPress tampoco se publica: solo Caddy expone 80/443.

---

## 8. Patrones y decisiones arquitectónicas detectadas

| Patrón o decisión | Implementación | Evidencia | Efecto |
|---|---|---|---|
| Monolito modular | Un despliegue WordPress con módulos separados por plugin, tema y mu-plugins | `docker-compose.prod.yml`, `plugins/`, `themes/`, `mu-plugins/` | Simplicidad operativa; acoplamiento al runtime de WordPress |
| Arquitectura en capas | Presentación, dominio/aplicación, persistencia y transversal | Estructura de archivos y dependencias descritas en la sección 3 | Separación razonable sin interfaces formales |
| Service layer | `Tanish_Inventory_Service` concentra reglas y validaciones | `class-tanish-inventory-service.php` | El controlador admin no implementa reglas de stock |
| Repository / DB layer | `Tanish_Inventory_Database` encapsula tabla, esquema y consultas | `class-tanish-inventory-database.php` | Cambios de esquema y SQL quedan en un solo archivo |
| Gateway pattern | Subclases de `WC_Payment_Gateway` registradas por filtro | `class-tanish-yape-gateway.php`, `class-tanish-mercadopago-link.php` | WooCommerce trata las pasarelas como cualquier método nativo |
| Autorización por capabilities | `manage_tanish_inventory`; roles `almacen` y `ventas`; filtro de acceso al admin | `class-tanish-inventory-capabilities.php` | Permisos granulares sin crear un sistema de auth propio |
| Event-driven interno | `add_action` / `add_filter` para ciclo de vida, admin, WooCommerce y correo | Múltiples archivos | Bajo acoplamiento dentro del proceso; no hay bus ni persistencia de eventos |
| mu-plugins para preocupaciones transversales | Seguridad, formularios, cron, branding y SMTP | `mu-plugins/` | Se cargan siempre, sin activación; no dependen del tema |
| SSR con jerarquía de plantillas | `front-page.php`, `archive-product.php`, `page-{slug}.php`, overrides de WooCommerce | `themes/tanish-storefront/` | HTML inicial completo y SEO directo; menos interactividad sin JS |
| PRG + toast con transient | Redirección 303 tras formularios y mensaje de un solo uso | `functions.php`, líneas 818-872 | Evita reenvío al refrescar; el mensaje no viaja en la URL |
| Fragmentos AJAX de WooCommerce | `woocommerce_add_to_cart_fragments` refresca contador y drawer | `inc/cart-drawer.php`, líneas 94-104 | Carrito lateral coherente sin recargar la página |
| Caché de lectura con transients | Estadísticas de inventario, ventas y series cacheadas 5 minutos | `tanish-admin-branding.php`, líneas 169-301 | Menos consultas en el dashboard; depende de la base de datos |
| Transacción compensatoria | Si falla el insert del movimiento, se restaura el stock | `class-tanish-inventory-service.php`, líneas 134-140 | Evita desincronización básica; no elimina condiciones de carrera |
| Configuración por entorno | MySQL, SMTP y URL de WordPress desde variables de Docker | `docker-compose.prod.yml`, `brevo-smtp.php` | Mismo código para dev/prod; secretos fuera del repositorio |
| Despliegue idempotente | `git pull --ff-only`, `docker compose up -d --remove-orphans`, sin `down -v` | `scripts/deploy-production.sh` | Evita destruir volúmenes; no hay rollback automático |
| Proxy de borde | Cloudflare Worker + Caddy | `workers/`, `caddy/Caddyfile` | TLS y reescritura en el borde; dos capas de proxy que mantener |

### 8.1 Sobre la “fuente única de verdad”

La decisión es explícita: **WooCommerce gestiona el stock** y el plugin agrega un historial de movimientos manuales. El baseline lo confirma (`SPRINT-00-BASELINE.md`, línea 120: `woocommerce_manage_stock=yes`). La tabla propia no reemplaza el stock de WooCommerce; lo audita parcialmente.

Esto es adecuado para el tamaño actual, pero implica que:

- El stock visible en la tienda y el stock del ledger pueden divergir si hay ventas.
- El kardex es un historial de operaciones manuales, no un libro completo de inventario.
- Cualquier reporte de inventario que combine ventas y ajustes debe consultar ambas fuentes.

### 8.2 Sobre la autorización

El plugin no crea un sistema de autenticación paralelo. Usa usuarios, roles y capabilities de WordPress:

- `manage_tanish_inventory` controla el menú y las acciones del plugin.
- `almacen` recibe `read`, `manage_tanish_inventory` y `edit_products`.
- `ventas` recibe `read`, `edit_products`, `edit_shop_orders` y `read_private_shop_orders`.
- `administrator` y `shop_manager` reciben `manage_tanish_inventory`.
- El filtro `woocommerce_prevent_admin_access` permite el acceso al personal con la capability o con `edit_shop_orders`.

Los formularios administrativos usan `check_admin_referer()`; los formularios públicos usan `wp_verify_nonce()`. La página de acceso usa `wp_signon()` y un rate limit de 10 intentos por IP cada 10 minutos.

---

## 9. Limitaciones y deuda técnica

| ID | Limitación | Evidencia | Consecuencia |
|---|---|---|---|
| L1 | No es headless ni tiene frontend separado | Tema PHP con jerarquía de plantillas; no hay SPA | El render y la lógica de presentación dependen de WordPress |
| L2 | No hay API de dominio propia | Solo la REST API estándar de WordPress, que el mu-plugin de seguridad no desactiva | Integraciones externas dependen de la API genérica o de scraping |
| L3 | No hay cola de aplicación ni bus de eventos | Hooks en proceso; Action Scheduler solo para tareas de WooCommerce | El correo es síncrono y, en desarrollo, las tareas de Action Scheduler se ejecutan dentro de la petición |
| L4 | Envío de correo síncrono | `wp_mail()` en el flujo del formulario | Si SMTP falla, el usuario espera y ve un error aunque el registro ya esté guardado |
| L5 | Single-node sin alta disponibilidad | Un MySQL, un WordPress y un Caddy en el mismo VPS | Caída del host o del contenedor deja el sitio fuera de servicio |
| L6 | Sin suite de pruebas automatizadas | CI solo ejecuta `php -l`, `docker compose config` y ShellCheck; no hay `tests/` | Las regresiones se detectan manualmente |
| L7 | Sin transacción ni bloqueo en inventario | `apply_movement()` lee, calcula, guarda y luego inserta | Dos movimientos concurrentes pueden producir pérdida de actualización |
| L8 | El ledger no registra ventas | Las pasarelas llaman a `wc_reduce_stock_levels()` / `reduce_stock_levels()` sin insertar movimiento | El kardex está incompleto para auditoría de inventario |
| L9 | Sin foreign keys ni constraints de dominio | Esquema de `wp_tanish_inventory_movements` | Registros huérfanos y tipos de movimiento no validados por la base |
| L10 | `WP_DEBUG` está fijado en `true` | `wp-config.php`, líneas 131-133 | La variable `WORDPRESS_DEBUG=0` de producción no evita el modo debug; los errores se registran, no se muestran |
| L11 | Sales y claves tienen valores por defecto en el repositorio | `wp-config.php`, líneas 78-85 | Si el entorno no las sobrescribe, se usan secretos conocidos |
| L12 | Dos Workers con lógica divergente | `tanish-proxy` y `tanish-mvp` | Mantenimiento duplicado y comportamiento distinto de cookies/redirects |
| L13 | Origen accesible sin autenticación de borde | Caddy publica 80/443 y el bloque `nip.io` | Se puede intentar saltar Cloudflare si se conoce el hostname del origen |
| L14 | IP y hostnames codificados | `wrangler.toml`, `Caddyfile`, `header.php`, `ui/hero-carousel.php` | Cambiar de VPS, Worker o CDN exige editar archivos y redeploy |
| L15 | Pasarelas simuladas o manuales | Yape simulado con número ficticio; Mercado Pago por link sin webhook | Los pedidos quedan `on-hold` y requieren confirmación humana |
| L16 | Sin MFA | `SPRINT-00-BASELINE.md` | La seguridad del panel depende de contraseña y rate limit |
| L17 | Sin caché de objetos ni de página | No hay Redis/Memcached/Varnish en Compose; Caddy solo comprime | Cada petición dinámica llega a PHP y MySQL |
| L18 | Observabilidad limitada | Solo `error_log` y logs de contenedores; sin métricas ni trazas | Diagnosticar incidentes depende de revisar logs manualmente |
| L19 | Imágenes flotantes en desarrollo | `wordpress:latest`, `mysql:8.0`, `caddy:2-alpine` | El entorno local puede diferir del runtime de producción |
| L20 | Restauración de producción no automatizada | `restore.sh` usa `docker-compose.yml` y `tanish-mysql` | Un restore real en el VPS requiere procedimiento manual |
| L21 | Documentación desalineada | `DEPLOYMENT.md` afirma que el Worker no aporta valor; el flujo actual lo usa; el baseline registra tema 2.0.0 y el código 3.0.0 | Riesgo de decisiones basadas en documentación obsoleta |
| L22 | Sin WAF ni rate limiting de borde en el repositorio | Los controles anti-spam y anti-fuerza bruta están en PHP con transients | La protección depende de la aplicación y de la configuración externa de Cloudflare |
| L23 | Dos integraciones de Mercado Pago | Gateway propio por link y plugin oficial instalado por script | Confusión operativa y posible duplicidad de métodos de pago |
| L24 | Sin pruebas de restauración ni de backup | Los workflows ejecutan backups, no verifican el restore | Un backup no validado puede fallar en el momento crítico |

### 9.1 Riesgos específicos del ledger de inventario

1. **Concurrencia:** el stock se lee y se escribe sin `SELECT ... FOR UPDATE` ni transacción. Dos usuarios podrían ajustar el mismo producto y perder uno de los cambios.
2. **Ventas no auditadas:** el kardex solo muestra entradas, salidas y ajustes manuales. Una venta reduce el stock de WooCommerce pero no crea una fila en `wp_tanish_inventory_movements`.
3. **Productos eliminados:** al no haber foreign key, los movimientos quedan apuntando a un `product_id` inexistente; el `LEFT JOIN` devuelve nombre vacío.
4. **Ajustes sin historial de venta:** un ajuste fija el stock real, pero no explica la diferencia frente al stock del sistema; el motivo es texto libre.

### 9.2 Riesgos específicos de la configuración

1. **Debug en producción:** `WP_DEBUG` está definido en `true` de forma incondicional. `WP_DEBUG_DISPLAY` está en `false`, por lo que no se muestran errores al visitante, pero se escribe un log y se activan comportamientos de debug.
2. **Sales por defecto:** las claves de autenticación tienen valores literales en `wp-config.php`. Si el entorno no las define, el sitio usa valores públicos del repositorio.
3. **Bypass del borde:** el origen HTTP en `nip.io` no exige un secreto compartido ni una allowlist de Cloudflare. La única protección es que el hostname no sea evidente.
4. **Dependencia de `nip.io`:** el Worker no puede hacer `fetch()` a una IP directa; por eso usa un hostname que resuelve a la IP. Eso agrega una dependencia de DNS de terceros en la ruta crítica.

---

## 10. Conclusión y clasificación

### 10.1 Clasificación final

| Criterio | Clasificación |
|---|---|
| Estilo | Monolito modular |
| Capas | Presentación, dominio/aplicación, persistencia, transversal y plataforma |
| Renderizado | SSR con PHP y jerarquía de plantillas de WordPress |
| Persistencia | Una base de datos MySQL compartida |
| Despliegue | Single-node contenerizado |
| Borde | Proxy inverso en Caddy y proxy de borde en Cloudflare Workers |
| Comunicación interna | Llamadas en proceso y hooks de WordPress |
| Autorización | Capabilities y roles de WordPress |
| Madurez | MVP operativo con deuda técnica conocida |

TANISH **no es** una arquitectura de microservicios, **no es** headless, **no es** una arquitectura orientada a servicios y **no** tiene un frontend separado. Es un monolito WordPress/WooCommerce bien delimitado por módulos, con una separación razonable entre presentación, reglas de inventario, acceso a datos y preocupaciones transversales.

### 10.2 Por qué es adecuado para 12 productos

Para un catálogo de 12 productos, 15 categorías, un usuario administrativo y tráfico de MVP:

- **Un solo despliegue** reduce el costo operativo y evita coordinar versiones entre servicios.
- **Una sola base de datos** elimina transacciones distribuidas y problemas de consistencia entre servicios.
- **WooCommerce** ya resuelve catálogo, carrito, checkout, pedidos, impuestos y stock; reconstruirlo no aporta valor con este volumen.
- **SSR** entrega HTML completo, buen SEO y una experiencia simple sin un build de frontend.
- **El plugin de inventario** agrega el valor diferencial (entradas, salidas, ajustes y kardex) sin reemplazar la plataforma comercial.
- **Caddy + Cloudflare** dan TLS, compresión y una capa de borde con muy poca configuración.
- **Los backups y el despliegue** son scripts pequeños y auditables, adecuados para un equipo reducido.

El límite de esta arquitectura aparece cuando el negocio necesite:

- auditar **todas** las variaciones de stock, incluidas las ventas;
- operar varias bodegas o ubicaciones;
- soportar alta concurrencia sobre el mismo producto;
- separar el panel administrativo del sitio público;
- integrar ERP, facturación electrónica o analítica avanzada;
- escalar horizontalmente la aplicación o la base de datos;
- garantizar alta disponibilidad y recuperación automatizada.

Ninguna de esas necesidades está implementada hoy. La arquitectura actual es coherente con el tamaño del negocio y con el objetivo de un MVP; la evolución debe hacerse de forma incremental y sin romper WooCommerce como plataforma de comercio.

---

## 11. Referencias a los archivos usados como evidencia

| Archivo | Uso como evidencia |
|---|---|
| `docker-compose.yml` | Servicios de desarrollo, Mailpit, volúmenes, healthcheck de MySQL y `DISABLE_WP_CRON` |
| `docker-compose.prod.yml` | Servicios de producción, imágenes, healthchecks, volúmenes, montajes de solo lectura y configuración de WordPress |
| `caddy/Caddyfile` | TLS, gzip, proxy a `wordpress:80`, bloque `nip.io` y fallback 404 |
| `workers/tanish-proxy/src/index.js` | Reescritura de URL, cabeceras `X-Forwarded-*`, 502 y limpieza de `alt-svc` |
| `workers/tanish-proxy/wrangler.toml` | Nombre, entrypoint y origen fijo del Worker principal |
| `workers/tanish-mvp/src/index.ts` | Proxy alternativo, reescritura de cookies y redirects, páginas 502/503 |
| `workers/tanish-mvp/wrangler.jsonc` | Entornos y `ORIGIN_URL` vacía |
| `workers/tanish-mvp/README.md` | Contrato de configuración y comportamiento de error |
| `plugins/tanish-inventory/tanish-inventory.php` | Bootstrap, constantes, activación y carga condicional de WooCommerce |
| `plugins/tanish-inventory/includes/class-tanish-inventory-database.php` | Esquema exacto, `dbDelta`, inserción, movimientos recientes, kardex y serie diaria |
| `plugins/tanish-inventory/includes/class-tanish-inventory-service.php` | Reglas de stock, validaciones, guardado y compensación |
| `plugins/tanish-inventory/includes/class-tanish-inventory-admin.php` | Menús, procesamiento de formularios, nonces, dashboard, movimientos y kardex |
| `plugins/tanish-inventory/includes/class-tanish-inventory-capabilities.php` | Capability, roles y acceso al admin |
| `plugins/tanish-inventory/includes/class-tanish-cpts.php` | CPTs de banners y videos, meta boxes y columnas |
| `plugins/tanish-inventory/includes/class-tanish-whatsapp.php` | Opción y pantalla de configuración de WhatsApp |
| `plugins/tanish-inventory/includes/class-tanish-yape-gateway.php` | Pasarela Yape simulada, estado del pedido y reducción de stock |
| `plugins/tanish-inventory/includes/class-tanish-mercadopago-link.php` | Pasarela de link de Mercado Pago y confirmación manual |
| `mu-plugins/tanish-security.php` | XML-RPC desactivado y `nosniff` |
| `mu-plugins/tanish-forms.php` | CPTs de reclamos/contacto, IP real y guardado de envíos |
| `mu-plugins/tanish-cron.php` | Ejecución de cron en desarrollo sin loopback |
| `mu-plugins/tanish-admin-branding.php` | Branding, dashboard, estadísticas y navegación administrativa |
| `mu-plugins/brevo-smtp.php` | Configuración SMTP, remitente y logging de fallos |
| `themes/tanish-storefront/functions.php` | Soporte del tema, assets, traducciones, WhatsApp, GTM/GA4, formularios y toast |
| `themes/tanish-storefront/header.php` | Cabecera, búsqueda, carrito y sesión |
| `themes/tanish-storefront/footer.php` | Pie y dock de WhatsApp |
| `themes/tanish-storefront/front-page.php` | Composición de la portada |
| `themes/tanish-storefront/archive-product.php` | Catálogo y sidebar |
| `themes/tanish-storefront/woocommerce/single-product.php` | Layout de producto y hooks de WooCommerce |
| `themes/tanish-storefront/page-acceso.php` | Login con `wp_signon()`, rate limit y redirección por rol |
| `themes/tanish-storefront/page-contacto.php` | Formulario de contacto, validación, anti-spam y correo |
| `themes/tanish-storefront/page-libro-de-reclamaciones.php` | Formulario INDECOPI, correlativo y correos |
| `themes/tanish-storefront/inc/cart-drawer.php` | Carrito lateral y fragmentos AJAX |
| `themes/tanish-storefront/inc/email-template.php` | Plantilla HTML de correo |
| `themes/tanish-storefront/inc/performance.php` | Preload, lazy loading y prefetch |
| `themes/tanish-storefront/style.css` | Identidad visual y versión declarada del tema |
| `scripts/backup.sh` | Backup local de MySQL |
| `scripts/backup-production.sh` | Backup de producción: base, uploads y configuración/código |
| `scripts/deploy-production.sh` | Validación, `git pull`, `up -d` y verificación de salud |
| `scripts/restore.sh` | Restauración local de la base |
| `scripts/setup-production.sh` | Instalación del plugin oficial de Mercado Pago, traducciones y permisos |
| `.github/workflows/ci.yml` | Lint PHP, validación de Compose y ShellCheck |
| `.github/workflows/deploy.yml` | Despliegue por SSH tras push a `main` |
| `.github/workflows/backup.yml` | Backup semanal y artefacto de GitHub |
| `wp-config.php` | Configuración por entorno, sales por defecto y constantes de debug |
| `docs/DEPLOYMENT.md` | Guía de despliegue, persistencia, backups y Worker |
| `docs/sprints/SPRINT-00-BASELINE.md` | Versiones de runtime, conteos y estado de componentes |
| `docs/arquitectura/README.md` | Contexto documental y separación entre arquitectura actual y propuesta |

---

### Nota final de alcance

Este documento refleja el estado del repositorio en la rama `docs/arquitectura-laravel` al 2026-11-23. Las versiones de runtime provienen del snapshot de `SPRINT-00`; las que no pueden verificarse sin ejecutar los contenedores se identifican como tales. Las cifras de líneas son conteos físicos de los archivos citados y se incluyen solo como referencia de tamaño, no como métrica de calidad.
