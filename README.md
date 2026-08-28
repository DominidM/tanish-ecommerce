# TANISH E-Commerce Platform

Plataforma E-Business desarrollada para **TANISH S.A.C.**, orientada a comercio electrónico y gestión interna de inventario.

El sistema integra una tienda virtual basada en WordPress + WooCommerce con un módulo propio de control de inventario para alinear las ventas online con el stock físico y los procesos internos de la empresa.

> Repositorio oficial: https://github.com/DominidM/tanish-ecommerce.git

---

## Descripción

El sistema permitirá:

- **Catálogo de productos** con categorías, variaciones, precios y stock
- **Comercio electrónico** completo (tienda pública)
- **Carrito de compras** y **checkout** integrado
- **Gestión de pedidos** (estados, detalle, cliente, comprobantes)
- **Control de stock** nativo de WooCommerce
- **Entradas de inventario** (compras, devoluciones, producción)
- **Salidas de inventario** (ventas, mermas, transferencias)
- **Ajustes de inventario** (conteos físicos, correcciones)
- **Historial / Kardex** lógico de todos los movimientos
- **Analítica web** para medición de tráfico y conversión

**WooCommerce será el núcleo comercial.**

El plugin propio:

**`tanish-inventory`**

será responsable de ampliar la gestión de inventario por encima de las capacidades estándar de WooCommerce, implementando:

- Kardex lógico y trazabilidad completa
- Tipos de movimiento auditables
- Stock vinculado bidireccionalmente con productos de WooCommerce
- Usuarios y roles autorizados para modificar inventario
- Reportes y conciliación

### Alcance inicial

La primera versión **NO** tendrá integración automática con **SUNAT**. La emisión electrónica de boletas/facturas seguirá siendo manejada externamente por el sistema contable actual de TANISH S.A.C.

La integración con SUNAT (facturación electrónica) se evaluará como segunda versión.

---

## Tecnologías

### Infraestructura

- **Ubuntu 24.04 LTS (Noble Numbat)**
- **Docker Engine** (29.x)
- **Docker Compose** (v2.40+)

### Backend / CMS

- **WordPress** (imagen oficial `wordpress:latest`)
- **PHP 8.3 + Apache** (incluido en imagen WordPress)

### E-Commerce

- **WooCommerce** (instalación posterior vía wp-admin)

### Base de datos

- **MySQL 8.0** (imagen oficial `mysql:8.0`)
  - Database: `tanish_wordpress`
  - User: `tanish_wp`
  - Credenciales gestionadas por `.env`

### Control de versiones

- **Git** (2.43+)
- **GitHub** — https://github.com/DominidM/tanish-ecommerce

### Analítica futura

- **Google Analytics 4 (GA4)**
- **Google Search Console**

### Desarrollo propio

- **Plugin WordPress: `tanish-inventory`** — `plugins/tanish-inventory/`
  - Entradas / Salidas / Ajustes
  - Historial / Kardex
  - Trazabilidad
  - Roles y permisos

---

## Arquitectura

```
Cliente
   |
   v
WordPress  (PHP + Apache)  — :8080
   |
WooCommerce
   |
   +----------------+
   |                |
Pedidos         Productos
                    |
                    v
                 Stock (WooCommerce)
                    |
              tanish-inventory
                    |
        +-----------+-----------+
        |           |           |
     Entradas    Salidas     Ajustes
                    |
                    v
              Historial/Kardex
                    |
                    v
                  MySQL  (tanish_wordpress)
```

**Despliegue local con Docker:**

```
Ubuntu 24.04
   └── Docker
        ├── WordPress (PHP + Apache) ── tanish-wordpress :8080 -> 80
        │      └── volumen: tanish_wordpress_data:/var/www/html
        └── MySQL 8.0 ── tanish-mysql :3306 (interno)
               └── volumen: tanish_mysql_data:/var/lib/mysql
               └── red: tanish-network (bridge)
```

- WordPress se conecta a MySQL mediante la red interna `tanish-network` usando `WORDPRESS_DB_HOST=mysql:3306`.
- MySQL **no expone** puerto al host (solo interno) por seguridad.
- Volúmenes persistentes `tanish_mysql_data` y `tanish_wordpress_data` conservan datos entre reinicios.

---

## Estructura del Proyecto

```
tanish-ecommerce/
├── docker-compose.yml          # Orquestación WordPress + MySQL
├── .env                        # Variables reales locales (NO se sube a Git)
├── .env.example                # Plantilla de variables (SÍ se sube)
├── .gitignore
├── README.md
│
├── plugins/
│   └── tanish-inventory/       # Plugin propio TANISH Inventory
│       ├── tanish-inventory.php
│       ├── includes/
│       │   └── class-tanish-whatsapp.php  # Integración WhatsApp
│       └── assets/
│           └── tanish-storefront.css      # Estilos sobrios v0.2.1
│
├── docs/
│   ├── arquitectura/
│   ├── requisitos/
│   ├── pruebas/
│   └── evidencias/
│
└── scripts/
    ├── setup-wordpress.php     # Portada + limpieza (idempotente)
    └── improve-storefront.php  # Mejora visual + menú (idempotente)
```

> **Nota:** WordPress core **no se sube** al repositorio. Se ejecuta desde la imagen oficial `wordpress:latest` y persiste en el volumen Docker `tanish_wordpress_data`.

---

## Requisitos

| Herramienta | Versión mínima | Verificación |
|---|---|---|
| Ubuntu | 24.04 LTS | `lsb_release -a` |
| Docker Engine | 29.x | `docker --version` |
| Docker Compose | v2.40+ | `docker compose version` |
| Git | 2.43+ | `git --version` |

No es necesario instalar Apache, PHP ni MySQL en el host — todo corre en Docker.

---

## Instalación

### 1. Clonar repositorio

```bash
git clone https://github.com/DominidM/tanish-ecommerce.git
cd tanish-ecommerce
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
# Editar .env y reemplazar CHANGE_ME por contraseñas seguras
nano .env
```

**.env.example:**

```ini
MYSQL_DATABASE=tanish_wordpress
MYSQL_USER=tanish_wp
MYSQL_PASSWORD=CHANGE_ME
MYSQL_ROOT_PASSWORD=CHANGE_ME

WORDPRESS_DB_HOST=mysql:3306
WORDPRESS_DB_NAME=tanish_wordpress
WORDPRESS_DB_USER=tanish_wp
WORDPRESS_DB_PASSWORD=CHANGE_ME
```

> `.env` está en `.gitignore` y nunca debe subirse al repositorio.

### 3. Validar configuración

```bash
docker compose config
```

Si no hay errores, continuar.

### 4. Levantar servicios

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=50
```

Esperar ~30s a que MySQL pase el `healthcheck` (`healthy`) y WordPress copie los archivos.

---

## Acceso

- **Instalador WordPress:** http://localhost:8080
  - Redirige automáticamente a `http://localhost:8080/wp-admin/install.php`
- **Administración (post-instalación):** http://localhost:8080/wp-admin

**Primer uso:**

1. Abrir http://localhost:8080
2. Seleccionar idioma
3. Crear usuario administrador, contraseña y email
4. Instalar WooCommerce desde `Plugins > Añadir nuevo` (paso posterior)

---

## Comandos Útiles

### Iniciar servicios

```bash
docker compose up -d
```

### Detener servicios (conserva datos)

```bash
docker compose down
```

### Ver estado

```bash
docker compose ps
```

### Ver logs

```bash
docker compose logs -f
docker compose logs --tail=50
docker compose logs wordpress
docker compose logs mysql
```

### Reiniciar

```bash
docker compose restart
```

### Entrar a contenedores

```bash
docker exec -it tanish-wordpress bash
docker exec -it tanish-mysql bash
docker exec -it tanish-mysql mysql -u tanish_wp -p
```

### Validar configuración

```bash
docker compose config
```

---

## Importante sobre los Datos

### `docker compose down`

Detiene y **elimina** los contenedores y la red, **pero conserva** los volúmenes persistentes:

- `tanish_mysql_data`
- `tanish_wordpress_data`

> **Los datos de WordPress y la base de datos se conservan.** Puedes hacer `up -d` nuevamente y todo seguirá ahí.

### `docker compose down -v` — ¡PELIGRO!

El flag `-v` **ELIMINA también los volúmenes persistentes**.

```bash
docker compose down -v  # BORRA base de datos y archivos de WordPress
```

- Borra `tanish_mysql_data` → pierdes toda la BD (`tanish_wordpress`)
- Borra `tanish_wordpress_data` → pierdes `wp-config`, plugins, uploads, temas

> **NO utilizar `docker compose down -v` salvo que realmente quieras eliminar la base de datos y comenzar desde cero.**

**Backup recomendado antes de cualquier `down -v`:**

```bash
docker exec tanish-mysql mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" tanish_wordpress > backup_$(date +%F).sql
```

---

## Integración comercial por WhatsApp

La versión inicial **no utiliza el checkout tradicional de WooCommerce**. En su lugar, implementa un flujo comercial ligero y directo vía WhatsApp, manteniendo a WooCommerce como fuente de verdad del catálogo.

### WooCommerce como:

- **Catálogo** de productos (nombre, categorías, imágenes)
- **Gestor de productos** (SKU, descripciones, atributos)
- **Gestión de precios** (`get_price_html` / `wc_price`)
- **Gestión de stock** (`is_in_stock`, `is_purchasable`) — fuente única de verdad

### WhatsApp como:

- **Canal de contacto** inmediato
- **Canal inicial de cierre comercial** (TANISH confirma disponibilidad y coordina el pedido manualmente)

### Flujo

```
Producto (WooCommerce)
  → Comprar por WhatsApp (botón en single product, hook woocommerce_single_product_summary:30)
  → Mensaje precargado vía https://wa.me/{numero}?text={mensaje}
  → Atención comercial TANISH por WhatsApp
```

**Ejemplo de mensaje precargado (codificado con `rawurlencode`):**

```
Hola, deseo comprar este producto de TANISH.

Producto: Coca-Cola 1.5 L
SKU: BEB-001
Precio: S/ 8.00
Enlace: http://localhost:8080/product/coca-cola-1-5-l/

¿Podrían confirmarme disponibilidad y coordinar el pedido?
```

Datos tomados dinámicamente del producto actual: `get_name()`, `get_sku()` (si existe), `get_price_html()` (strip tags), `get_permalink()`.

### Configuración

- **Ubicación:** WordPress Admin → **WooCommerce → TANISH WhatsApp**
- **Capability:** `manage_woocommerce`
- **Campo:** *Número de WhatsApp* — solo números, con código de país, sin `+` ni espacios
- **Formato:** `51987654321` (ejemplo Perú) — sanitizado con `sanitize_text_field` + `preg_replace('/\D/', '', $value)`
- **Ayuda en página:** *“Ingresa el número en formato internacional sin + ni espacios. Ejemplo para Perú: 51987654321”*
- **Sin número configurado:** el botón no genera enlaces inválidos (no se muestra)

### Comportamiento de stock

- **Producto disponible (`is_in_stock() === true` y `is_purchasable()`):** muestra botón `Comprar por WhatsApp` (`<a href="https://wa.me/..." target="_blank" rel="noopener noreferrer" class="button alt">`)
- **Producto agotado:** **NO** muestra botón — WooCommerce sigue siendo la única fuente de stock, sin sistema paralelo ni columnas duplicadas ni `wp_postmeta` directo

### Carrito y Checkout

- **Single product:** se elimina `Añadir al carrito` vía `remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30)` en hook `wp` — reversible fácilmente comentando la línea para reactivar checkout tradicional.
- **Catálogo/archivo:** `woocommerce_loop_add_to_cart_link` se filtra para mostrar **“Ver producto”** enlazando al detalle (`get_permalink`) en lugar de añadir directo al carrito, guiando al usuario hacia el botón WhatsApp. No se borran páginas `Cart`/`Checkout`.

### Compatibilidad

- **Soporte inicial optimizado para productos simples (`is_type('simple')`).** Variables, bundles, subscriptions, descargables y externos quedan fuera del alcance v0.2.0 y se documentan para futuras versiones.

### Aclaración importante

> **Abrir WhatsApp NO descuenta stock automáticamente.** La salida de inventario se registrará **solo cuando la venta sea confirmada** mediante el módulo interno de inventario (`tanish-inventory` → entradas/salidas/ajustes/kardex).

> **La creación automática de pedidos (WooCommerce order) antes de abrir WhatsApp está planificada como una evolución futura** — actualmente el pedido se coordina manualmente por el equipo comercial.

### Seguridad y calidad

- `sanitize_text_field`, `preg_replace`, `esc_url`, `esc_html`, `esc_attr`
- `Settings API` + `manage_woocommerce` + `settings_fields()`/`do_settings_sections()`
- API pública WooCommerce (`WC_Product`, `get_name`, `get_sku`, `get_price_html`, `is_in_stock`, `is_purchasable`)
- Hooks oficiales (`woocommerce_single_product_summary`, `woocommerce_loop_add_to_cart_link`, `admin_menu`, `admin_init`, `plugins_loaded`)
- Sin SQL directo, sin `eval`, sin credenciales, sin librerías externas, sin lectura de `.env`
- Si WooCommerce no está activo: no produce fatal error, muestra aviso `admin_notices` y no ejecuta integración.

---

## Configuración inicial de WordPress

El archivo `scripts/setup-wordpress.php` automatiza la configuración inicial del storefront de forma **reproducible, versionada e idempotente** mediante APIs oficiales de WordPress (sin SQL directo).

### Qué automatiza

- **Creación de página `Inicio`** (`slug: inicio`, `status: publish`)
  - Si existe, la **actualiza** en lugar de duplicar (idempotente — no crea `Inicio (2)`)
  - Contenido comercial Gutenberg-compatible con:
    - `TANISH` + `Compra fácil y rápida`
    - Descripción y botón `[Ver productos]` → `/shop`
    - `Categorías` → `[product_categories number="6" columns="3" parent="0"]`
    - `Productos disponibles` → `[products limit="8" columns="4" orderby="date" order="DESC"]`
    - `¿Por qué comprar en TANISH?` + `¿Necesitas ayuda?` (WhatsApp)
  - Usa `get_page_by_path('inicio')` + `wp_insert_post()` / `wp_update_post()` + `get_posts()` — no hardcodea IDs de productos

- **Configuración de portada estática**
  - `update_option('show_on_front', 'page')`
  - `update_option('page_on_front', ID_de_Inicio)`
  - `update_option('page_for_posts', 0)` — sin página de entradas para este proyecto
  - Resultado: `http://localhost:8080` deja de mostrar el blog / `¡Hola, mundo!` y muestra `Inicio`

- **Limpieza de contenido de demostración**
  - `Página de ejemplo` / `Sample Page` (`sample-page`, `pagina-ejemplo`) → `wp_trash_post()` si existe y está publicada
  - `¡Hola, mundo!` / `Hello World` (`hola-mundo`, `hello-world`, `post` type) → `wp_trash_post()`
  - **No borra:** `Shop`, `Cart`, `Checkout`, `My Account`, `Política de privacidad`, `Refund and Returns Policy` — WooCommerce las requiere

- **Título y descripción**
  - `update_option('blogname', 'TANISH')`
  - `update_option('blogdescription', 'Compra fácil, rápida y directa.')`
  - **No modifica** `siteurl` / `home` (siguen en `http://localhost:8080`)

- **Conteo WooCommerce**
  - `wc_get_products(['limit'=>-1,'status'=>'publish'])` → reporta `WooCommerce products found: 12`

### Cómo ejecutar

Desde el host (recomendado, no requiere wp-cli en contenedor):

```bash
docker cp scripts/setup-wordpress.php tanish-wordpress:/tmp/setup-wordpress.php
docker exec tanish-wordpress php /tmp/setup-wordpress.php
docker exec tanish-wordpress rm /tmp/setup-wordpress.php
```

Salida esperada (idempotente):

```
TANISH WordPress Setup
----------------------
Inicio: created/updated (ID 35)
Front page: configured (page_on_front=35)
Sample page: trashed/already absent
Hello World: trashed/already absent
Blog name: TANISH
WooCommerce products found: 12
Setup completed successfully
```

### Idempotencia

El script es **idempotente**: ejecutarlo 2 o 10 veces **no** crea páginas duplicadas.

```bash
docker exec tanish-wordpress php /tmp/setup-wordpress.php  # 1ª: Inicio: created
docker exec tanish-wordpress php /tmp/setup-wordpress.php  # 2ª: Inicio: updated
```

Verificación:

```bash
# Debe existir exactamente una con slug inicio
wp shell: get_page_by_path('inicio') → 1 resultado
```

Funciones clave usadas: `wp_insert_post()`, `wp_update_post()`, `get_page_by_path()`, `get_posts()`, `update_option()`, `get_option()`, `wp_trash_post()`, `wc_get_products()` — todas oficiales, sin SQL directo.

---

## Mejora visual del storefront

Preparado para presentación académica: sobrio, moderno y comercial, sin Elementor ni plugins de diseño.

### Coming Soon

- **Causa:** `woocommerce_coming_soon = 'yes'` (WooCommerce activa pantalla morada para visitantes no autenticados)
- **Solución:** `update_option('woocommerce_coming_soon', 'no')` + `woocommerce_store_pages_only = 'no'` vía `scripts/improve-storefront.php` (idempotente). Se conserva `blog_public` sin romper configuración.

### Navegación

- **Antes:** `wp_navigation` con `<!-- wp:page-list /-->` mostraba todas las páginas (incluyendo Cart, Checkout, My account, Página de ejemplo)
- **Ahora:** `wp_navigation` ID 14 actualizado con 5 enlaces explícitos: **Inicio** (`/`, ID 35), **Tienda** (`?page_id=8`), **Categorías** (`/#categorias` anchor), **Nosotros** (ID 41), **Contacto** (ID 42) — Cart/Checkout/My account siguen existiendo pero fuera del menú principal.

### Páginas

- **Nosotros** (`/nosotros`, ID 41): texto institucional TANISH S.A.C., compromiso, stock transparente
- **Contacto** (`/contacto`, ID 42): indica canal WhatsApp, horario, enlace a Tienda y botón WhatsApp
- Ambas creadas idempotentemente con `get_page_by_path` + `wp_insert_post` si no existen

### Portada `Inicio` (ID 35)

Mejorada manteniendo Gutenberg y shortcodes, con clases versionadas `tanish-hero` / `tanish-section` / `tanish-benefits` / `tanish-contact`:

- **Hero:** `TANISH` H1, `Compra fácil y rápida` H2, texto breve, 2 botones `Ver productos → /shop` + `Comprar por WhatsApp → /contacto`
- **Categorías:** `[product_categories number="6" columns="3" parent="0"]` con `id="categorias"` para ancla del menú
- **Productos:** `[products limit="8" columns="4" orderby="date" order="DESC"]`
- **Beneficios:** grid 2x2 en `tanish-benefits` (Stock actualizado, Atención directa, Compra rápida, Catálogo online)
- **Contacto:** bloque oscuro `tanish-contact` con invitación WhatsApp

### Estilo visual

- **Archivo versionado:** `plugins/tanish-inventory/assets/tanish-storefront.css` (5.5 KB, `ver=0.2.1`) — documentado en repo, no `Customizer` suelto
- **Encole:** `wp_enqueue_style('tanish-storefront', plugin_dir_url() . 'assets/tanish-storefront.css', [], TANISH_INVENTORY_VERSION)` en `tanish-inventory.php:20`
- **Paleta sobria:** `slate-900` primario, `emerald-600` acento, `slate-500` muted, bordes `slate-200`, fondo `slate-50`
- **Jerarquía:** hero con gradiente, botones consistentes (`border-radius 8px`, hover `translateY`), cards de categorías/productos con `border-radius 12px`, `hover` sombra suave, ancho contenido `840px`, espaciado y tipografía limpia
- **Sin Elementor, sin frameworks CSS, sin modificar WooCommerce/WordPress core**
- **Categorías:** sin imágenes asignadas (thumbnails vacíos), pero CSS da placeholder digno con gradiente y cards elevadas — evita copyright dudoso

### Ejecución reproducible

```bash
docker cp scripts/improve-storefront.php tanish-wordpress:/tmp/improve-storefront.php
docker exec tanish-wordpress php /tmp/improve-storefront.php
docker exec tanish-wordpress rm /tmp/improve-storefront.php
# Salida: Coming soon: 'no' -> 'no' / Nosotros/Contacto created/exists / Inicio: updated / Menu: updated / Products found: 12
# Idempotente: segunda ejecución → Inicio: already professional, Nosotros: exists
```

---

## Roadmap

- [ ] Entorno Docker (WordPress + MySQL)
- [ ] Instalación WordPress
- [ ] Instalación WooCommerce
- [ ] Configuración catálogo (categorías, atributos, productos)
- [ ] Carrito y checkout
- [ ] Gestión de pedidos
- [ ] Control de stock WooCommerce
- [ ] Plugin `tanish-inventory` — scaffold y estructura
- [ ] Entradas de inventario
- [ ] Salidas de inventario
- [ ] Ajustes de inventario
- [ ] Kardex / historial de movimientos
- [ ] Trazabilidad completa
- [ ] Roles y permisos (quién puede mover stock)
- [ ] Google Analytics 4
- [ ] Google Search Console
- [ ] Pruebas (unitarias, integración, E2E)
- [ ] Despliegue (staging / producción)

---

## Alcance Actual

### La versión inicial SÍ incluye:

- E-commerce completo (catálogo, carrito, checkout, pedidos)
- Control de stock (WooCommerce + plugin propio)
- Gestión de inventario (entradas, salidas, ajustes, kardex)
- Usuarios y roles para inventario
- Analítica web (GA4 / Search Console)

### La versión inicial NO incluye:

- Integración automática con SUNAT
- ERP contable integrado
- Facturación electrónica propia (boletas/facturas)
- Reemplazo del sistema contable existente de TANISH S.A.C.

La emisión de comprobantes electrónicos seguirá haciéndose con la herramienta externa actual. Estas funcionalidades podrán evaluarse en futuras versiones del sistema.

---

## Contribución

```bash
git status
git add <archivos>
git commit -m "feat: descripción clara"
# git push  # solo con autorización
```

- No hacer `git push --force`
- No sobrescribir historia
- `.env` nunca se commitea (verificar con `git status`)

---

## Licencia

Proyecto privado de **TANISH S.A.C.** — Todos los derechos reservados.

---

## Autor

**Juan Dominid Muñoz Eslava** — [@DominidM](https://github.com/DominidM) — `dominidzero@gmail.com`

Desarrollado para **TANISH S.A.C.** — 2026
