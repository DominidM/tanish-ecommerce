# Matriz de Trazabilidad — REQUISITOS ↔ SPRINTS

## Leyenda

- **DONE**: Implementado, verificado, evidenciado
- **PARTIAL**: Implementado parcialmente o preparado sin activar
- **BLOCKED**: Depende de recurso externo no disponible
- **NOT STARTED**: No implementado

## Matriz de Requisitos

| ID | Sección Doc | Objetivo Académico | Requisito | Sprint | Componente | Estado Inicial | Estado Final | Evidencia | Prueba | Observaciones |
|---|---|---|---|---|---|---|---|---|---|---|
| REQ-ECO-01 | 3.4.2 OE1 | Catálogo de productos | 12 productos publicados con precios y stock | Sprint 00 | WooCommerce | DONE | DONE | `wp_posts` 12 productos | `/shop/` 200, 12 cards | Verificado en baseline |
| REQ-ECO-02 | 3.4.2 OE1 | Categorías de productos | 15 categorías configuradas | Sprint 00 | WooCommerce | DONE | DONE | `wp_term_taxonomy` 15 cat | `/shop/` muestra categorías | Verificado en baseline |
| REQ-ECO-03 | 3.4.2 OE1 | Precios en productos | Todos los productos tienen precio | Sprint 00 | WooCommerce | DONE | DONE | `wp_postmeta._price` | Productos muestran precio | Verificado en baseline |
| REQ-ECO-04 | 3.4.2 OE1 | Stock WooCommerce | Stock gestionado por WooCommerce | Sprint 00 | WooCommerce | DONE | DONE | `wp_postmeta._stock` | 12 productos con stock | Verificado en baseline |
| REQ-ECO-05 | 3.4.2 OE1 | Carrito funcional | Página /cart/ funcional con add_to_cart | Sprint 01 | WooCommerce/Theme | NOT STARTED | | | | `remove_action` en class-tanish-whatsapp.php:158 elimina add_to_cart |
| REQ-ECO-06 | 3.4.2 OE1 | Checkout funcional | Formulario WooCommerce en /checkout/ | Sprint 01 | WooCommerce | NOT STARTED | | | | Actualmente redirige (HTTP 302) |
| REQ-ECO-07 | 3.4.2 OE1 | Creación de pedidos | Pedido WooCommerce generado tras checkout | Sprint 01 | WooCommerce | NOT STARTED | | | | No hay creación automática |
| REQ-ECO-08 | 3.4.2 OE1 | Stock tras pedido | WooCommerce descuenta stock al confirmar pedido | Sprint 01 | WooCommerce | NOT STARTED | | | | Requiere REQ-ECO-07 |
| REQ-ECO-09 | Abstract | Flujo B2C completo | catálogo → producto → carrito → checkout → pedido | Sprint 01 | Múltiples | NOT STARTED | | | | Flujo actual es catálogo → WhatsApp |
| REQ-ECO-10 | Abstract | WhatsApp como alternativa | Canal WhatsApp coexistente con B2C | Sprint 00 | class-tanish-whatsapp.php | DONE | DONE | Botón wa.me funcional | `/product/x/` muestra botón | Verificado en runtime |
| REQ-INV-01 | 3.4.2 OE2 | Módulo entradas | Plugin registra entradas de inventario | Sprint 00 | tanish-inventory | DONE | DONE | Admin page `tanish-inventory-entry` | Página existe en wp-admin | Plugin funcional |
| REQ-INV-02 | 3.4.2 OE2 | Módulo salidas | Plugin registra salidas de inventario | Sprint 00 | tanish-inventory | DONE | DONE | Admin page `tanish-inventory-exit` | Página existe en wp-admin | Plugin funcional |
| REQ-INV-03 | 3.4.2 OE2 | Módulo ajustes | Plugin registra ajustes de stock | Sprint 00 | tanish-inventory | DONE | DONE | Admin page `tanish-inventory-adjustment` | Página existe en wp-admin | Plugin funcional |
| REQ-INV-04 | 3.4.2 OE2 | Kardex | Historial cronológico de movimientos | Sprint 00 | tanish-inventory | DONE | DONE | Admin page `tanish-inventory-kardex` | Página existe en wp-admin | Plugin funcional |
| REQ-INV-05 | Abstract | Trazabilidad completa | user, fecha, motivo, stock_before, stock_after | Sprint 00 | wp_tanish_inventory_movements | DONE | DONE | Schema con todos los campos | Tabla verificada en MySQL | Schema correcto |
| REQ-INV-06 | 3.4.2 OE2 | Movimientos de demostración | Al menos 3 movimientos (entrada/salida/ajuste) | Sprint 02 | wp_tanish_inventory_movements | NOT STARTED | | | | Tabla tiene 0 registros |
| REQ-INV-07 | Abstract | WooCommerce fuente única stock | Plugin actualiza stock vía WC API | Sprint 00 | class-tanish-inventory-service.php | DONE | DONE | `set_stock_quantity()` + `save()` | Verificado en código | Sin duplicación |
| REQ-INV-08 | 3.4.2 OE2 | Validación salida > stock | Bloqueo cuando salida excede stock | Sprint 00 | class-tanish-inventory-service.php:84 | DONE | DONE | `WP_Error('insufficient_stock')` | Doble capa validación | Service + Admin |
| REQ-ROL-01 | 3.4.2 OE3 | Rol Administración | Acceso completo al sistema | Sprint 02 | wp_usermeta | DONE | DONE | `a:1:{s:13:"administrator";b:1;}` | 1 usuario admin | Solo 1 usuario existe |
| REQ-ROL-02 | 3.4.2 OE3 | Rol Almacén | Acceso a inventario, sin config global | Sprint 02 | class-tanish-inventory-capabilities.php | NOT STARTED | | | | Solo administrator/shop_manager |
| REQ-ROL-03 | 3.4.2 OE3 | Rol Ventas | Productos, pedidos, consulta stock | Sprint 02 | class-tanish-inventory-capabilities.php | NOT STARTED | | | | No existe este rol |
| REQ-ROL-04 | 3.4.2 OE3 | Capacidad manage_tanish_inventory | Asignada a roles apropiados | Sprint 00 | class-tanish-inventory-capabilities.php | DONE | DONE | `add_cap()` para admin/shop_manager | Verificado en código | Versión gated |
| REQ-ANA-01 | 3.4.2 OE4 | GA4 instalado | gtag.js o gtag config en frontend | Sprint 03 | theme/plugin | NOT STARTED | | | | Sin implementación en ningún archivo |
| REQ-ANA-02 | 3.4.2 OE4 | Evento view_item | Envío al ver producto individual | Sprint 03 | theme/plugin | NOT STARTED | | | | Requiere REQ-ANA-01 |
| REQ-ANA-03 | 3.4.2 OE4 | Evento add_to_cart | Envío al añadir al carrito | Sprint 03 | theme/plugin | NOT STARTED | | | | Requiere REQ-ANA-01 + REQ-ECO-05 |
| REQ-ANA-04 | 3.4.2 OE4 | Evento begin_checkout | Envío al iniciar checkout | Sprint 03 | theme/plugin | NOT STARTED | | | | Requiere REQ-ANA-01 + REQ-ECO-06 |
| REQ-ANA-05 | 3.4.2 OE4 | Evento purchase | Envío al completar compra | Sprint 03 | theme/plugin | NOT STARTED | | | | Requiere REQ-ANA-01 + REQ-ECO-07 |
| REQ-ANA-06 | Auditoría | Evento click_whatsapp | Tracking de clicks en WhatsApp | Sprint 03 | theme/plugin | NOT STARTED | | | | Funcionalidad real no documentada |
| REQ-ANA-07 | Tabla 7 | Google Search Console | Verificación + sitemap | Sprint 03 | WordPress | BLOCKED | | | | Requiere cuenta Google + propiedad |
| REQ-SEG-01 | 3.4.2 OE5 | HTTPS | TLS activo en producción | Sprint 04 | docker-compose.prod.yml/Caddyfile | PARTIAL | | | | Config preparada, no desplegada |
| REQ-SEG-02 | 3.4.2 OE5 | MFA | Autenticación multifactor administradores | Sprint 04 | WordPress plugin | NOT STARTED | | | | No hay plugin MFA |
| REQ-SEG-03 | 3.4.2 OE5 | Backups periódicos | Respaldo automático de BD | Sprint 04 | scripts/backup.sh | PARTIAL | | | | Script manual, sin cron |
| REQ-SEG-04 | 5.13 | Nonces | Verificación en formularios | Sprint 00 | class-tanish-inventory-admin.php | DONE | DONE | `check_admin_referer()` | Verificado en código | 3 nonces |
| REQ-SEG-05 | 5.13 | Capabilities | Control de acceso por rol | Sprint 00 | class-tanish-inventory-admin.php | DONE | DONE | `current_user_can()` | Verificado en código | En cada render |
| REQ-SEG-06 | 5.13 | Sanitización inputs | Limpieza de datos de entrada | Sprint 00 | class-tanish-inventory-admin.php | DONE | DONE | `absint()`, `sanitize_textarea_field()` | Verificado en código | Completo |
| REQ-SEG-07 | 5.13 | Escaping outputs | Protección XSS | Sprint 00 | templates, admin | DONE | DONE | `esc_html()`, `esc_url()` | Verificado en código | Completo |
| REQ-SEG-08 | 5.13 | MySQL privado | Puerto no expuesto al host | Sprint 00 | docker-compose.yml | DONE | DONE | Sin port mapping en mysql | Verificado en docker-compose.yml | Seguro |
| REQ-SEG-09 | 5.13 | .env ignorado | No versionado | Sprint 00 | .gitignore | DONE | DONE | `.env` en .gitignore | Verificado en .gitignore | Seguro |
| REQ-SEG-10 | 5.13 | Prepared statements | SQL injection protection | Sprint 00 | class-tanish-inventory-database.php | DONE | DONE | `$wpdb->prepare()` | Verificado en código | Queries parametrizadas |
| REQ-PRU-01 | 6.2 | Pruebas unitarias | PHPUnit para lógica inventario | Sprint 05 | tests/ | NOT STARTED | | | | Sin directorio tests/ |
| REQ-PRU-02 | 6.2.2 | Pruebas aceptación UAT | 12 casos de prueba documentados | Sprint 05 | docs/pruebas/UAT.md | NOT STARTED | | | | UAT definidos en UAT.md, NO ejecutados |
| REQ-PRU-03 | Auditoría | Smoke tests | HTTP 200 en páginas principales | Sprint 05 | tests/ | NOT STARTED | | | | Sin suite de tests |
| REQ-ACA-01 | 5.7 | Business Case | Documento 5.7 completo | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-02 | 5.8 | Flujograma | Diagrama de procesos | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-03 | 5.9 | Cronograma/EDT | EDT + cronograma | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-04 | 5.10 | Plan de Riesgos | Matriz de riesgos | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-05 | 5.11 | Interesados | Registro de interesados | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-06 | 5.12 | Presupuesto | Presupuesto del proyecto | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-07 | 5.13 | Plan de Seguridad | Controles documentados | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-08 | 5.14 | Plan de Calidad | Criterios + pruebas | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-09 | 6 | Metodología | Descripción híbrida | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-10 | 6.3 | Despliegue | Arquitectura documentada | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-11 | 7 | Resultados | Estructura preparada | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-ACA-12 | 8 | Conclusiones | Estructura preparada | Sprint 06 | docs/academic/ | NOT STARTED | | | | Sección vacía en doc |
| REQ-VER-01 | Auditoría | Versiones reales | Documentar versiones verificadas | Sprint 06 | docs/sprints/SPRINT-00-BASELINE.md | DONE | DONE | Snapshot versiones en baseline | Tabla versiones verificadas | Versiones documentadas en Sprint 00 |

## Resumen por Sprint

| Sprint | Total Requisitos | DONE | PARTIAL | BLOCKED | NOT STARTED |
|---|---|---|---|---|---|
| Sprint 00 | 20 | 20 | 0 | 0 | 0 |
| Sprint 01 | 5 | 0 | 0 | 0 | 5 |
| Sprint 02 | 4 | 1 | 0 | 0 | 3 |
| Sprint 03 | 7 | 0 | 0 | 1 | 6 |
| Sprint 04 | 3 | 0 | 2 | 0 | 1 |
| Sprint 05 | 3 | 0 | 0 | 0 | 3 |
| Sprint 06 | 13 | 1 | 0 | 0 | 12 |

## Resumen por Objetivo Académico

| OE | Descripción | Requisitos | Sprints | Estado Global |
|---|---|---|---|---|
| OE1 | Catálogo + carrito + checkout | REQ-ECO-01 a REQ-ECO-10 | Sprint 00 (parcial), Sprint 01 | PARTIAL |
| OE2 | Entradas/salidas/ajustes inventario | REQ-INV-01 a REQ-INV-08 | Sprint 00 (mayormente), Sprint 02 | PARTIAL (sin datos demo) |
| OE3 | Roles diferenciados | REQ-ROL-01 a REQ-ROL-04 | Sprint 00 (parcial), Sprint 02 | NOT STARTED (solo admin) |
| OE4 | GA4 + analítica | REQ-ANA-01 a REQ-ANA-07 | Sprint 03 | NOT STARTED |
| OE5 | HTTPS + MFA + backups | REQ-SEG-01 a REQ-SEG-03 | Sprint 04 | PARTIAL |
| Transversal | Seguridad código | REQ-SEG-04 a REQ-SEG-10 | Sprint 00 | DONE |
| Transversal | Pruebas | REQ-PRU-01 a REQ-PRU-03 | Sprint 05 | NOT STARTED |
| Transversal | Documentación académica | REQ-ACA-01 a REQ-ACA-12 | Sprint 06 | NOT STARTED |
| Transversal | Versiones verificadas | REQ-VER-01 | Sprint 06 | DONE |
