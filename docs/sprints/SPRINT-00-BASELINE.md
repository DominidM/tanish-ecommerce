# SPRINT 00 — Baseline y Trazaibilidad

## Nombre del Sprint
Sprint 00: Baseline y Trazaibilidad

## Objetivo
Congelar y documentar el estado actual del sistema TANISH antes de implementar cualquier cambio. Establecer el punto de partida medible para la trazabilidad.

## Justificación Académica
Preparación para la auditoría de alineación. Permite comparar el estado "ANTES" vs "DESPUÉS" de cada sprint.

## Secciones del Documento Relacionadas
- Todo el documento (baseline general)
- Tabla 4-7 (herramientas)

## Estado Inicial
Sistema funcionando en localhost:8080 con Docker. Plugin TANISH Inventory activo. WhatsApp como canal de venta. Sin carrito/checkout funcional. Sin GA4. Sin MFA. Sin roles diferenciados.

## Historias de Usuario
N/A — Este sprint es de documentación, no de funcionalidad.

## Requisitos Funcionales
N/A — Solo documentación del estado actual.

## Requisitos No Funcionales
- Snapshot técnico completo
- Versiones verificadas en runtime
- Estado de cada componente documentado

## Tareas Técnicas
1. Verificar versiones en runtime (WordPress, PHP, Apache, MySQL, Docker)
2. Contar productos, categorías, usuarios, páginas
3. Verificar estado de carrito, checkout, GA4, MFA, backups, HTTPS
4. Documentar capacidades y roles existentes
5. Verificar schema de tabla `wp_tanish_inventory_movements`
6. Confirmar que WooCommerce es fuente única de stock
7. Enlazar con auditoría técnica

## Criterios de Aceptación
- [x] Todas las versiones documentadas con fuente verificada
- [x] Estado de cada componente clasificado (DONE/PARTIAL/NOT STARTED)
- [x] TRACEABILITY.md actualizado
- [x] Auditoría enlazada

## Pruebas
N/A — Sprint de documentación.

## Evidencias Requeridas
- Snapshot de `docker compose ps`
- Versiones obtenidas de runtime
- Conteo de productos/categorías/usuarios
- Schema de tabla inventario
- Estado de cada endpoint HTTP

## Riesgos
Ninguno — No se modifica funcionalidad.

## Dependencias
Ninguna.

## Definition of Done
- [x] Baseline documentado en SPRINT-00-BASELINE.md
- [x] Auditoría enlazada
- [x] Requisitos identificados en TRACEABILITY.md
- [x] SPRINT-BOARD.md actualizado

## Estado Final
**STATUS: DONE**

## Archivos Modificados
- `docs/sprints/SPRINT-00-BASELINE.md` (este archivo)
- `docs/sprints/TRACEABILITY.md` (actualizado)
- `docs/sprints/SPRINT-BOARD.md` (creado)

## Resultado
Snapshot técnico completo del sistema. Todos los requisitos identificados y clasificados. Punto de partida establecido para medir progreso.

## Brechas Restantes
Ninguna — Este sprint es la línea base.

---

## Snapshot Técnico (evidencia)

### Versiones Verificadas en Runtime

| Componente | Versión | Fuente |
|---|---|---|
| Ubuntu | 24.04 LTS | Host del sistema |
| Docker Engine | 29.1.3 | `docker --version` |
| Docker Compose | 2.40.3 | `docker compose version` |
| WordPress | 7.1 | `wp-includes/version.php` |
| WooCommerce | 11.0.1 | `wp_options.woocommerce_version` |
| PHP | 8.3.33 | `php -v` en contenedor |
| Apache | 2.4.68 | `apache2 -v` en contenedor |
| MySQL | 8.0.46 | `mysql --version` en contenedor |
| TANISH Inventory | 0.3.0 | Plugin header |
| TANISH Storefront | 2.0.0 | Theme header |

### Estado de Componentes

| Componente | Estado | Evidencia |
|---|---|---|
| Productos | 12 publicados | `wp_posts WHERE post_type='product'` |
| Categorías | 15 (14 activas + 1 uncategorized) | `wp_term_taxonomy WHERE taxonomy='product_cat'` |
| Usuarios | 1 (tanish_admin) | `wp_users` |
| Roles | administrator | `wp_usermeta WHERE meta_key='wp_capabilities'` |
| Capacidad manage_tanish_inventory | Asignada a admin, shop_manager | `class-tanish-inventory-capabilities.php` |
| Páginas | 8 (Inicio, Tienda, Cart, Checkout, My Account, Nosotros, Contacto, Libro de Reclamaciones) | `wp_posts WHERE post_type='page'` |
| Carrito | Página existe (ID 9), sin contenido | `/cart/` HTTP 200, vacío |
| Checkout | Página existe (ID 10), redirige | `/checkout/` HTTP 302 |
| GA4 | No implementado | Sin gtag en código |
| MFA | No implementado | Sin plugin 2FA |
| Backups | Script manual existe | `scripts/backup.sh` |
| HTTPS | Preparado (prod), no activo | `docker-compose.prod.yml` |
| Pruebas | No implementadas | Sin directorio `tests/` |
| Tabla inventario | `wp_tanish_inventory_movements` | MySQL verificado |
| Movimientos | 0 registros | `SELECT COUNT(*) FROM wp_tanish_inventory_movements` |
| WhatsApp number | 51975852932 | `wp_options.tanish_whatsapp_number` |
| WooCommerce stock management | Activo | `woocommerce_manage_stock=yes` |
| HPOS orders | Activo | `woocommerce_custom_orders_table_enabled=yes` |
