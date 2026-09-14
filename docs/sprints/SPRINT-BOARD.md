# Sprint Board — TANISH E-Commerce

## Tablero Kanban

| ID | Sprint | Historia/Tarea | Prioridad | Estado | Dependencia | Evidencia |
|---|---|---|---|---|---|---|
| REQ-ECO-01 | 00 | Catálogo: 12 productos publicados | ALTA | DONE | - | `/shop/` 200, 12 cards |
| REQ-ECO-02 | 00 | Categorías: 15 configuradas | ALTA | DONE | - | `wp_term_taxonomy` 15 |
| REQ-ECO-03 | 00 | Precios en productos | ALTA | DONE | - | Productos muestran precio |
| REQ-ECO-04 | 00 | Stock WooCommerce activo | ALTA | DONE | - | 12 productos con stock |
| REQ-ECO-10 | 00 | WhatsApp como alternativa | ALTA | DONE | - | Botón wa.me funcional |
| REQ-INV-01 | 00 | Módulo entradas inventario | ALTA | DONE | - | Admin page existe |
| REQ-INV-02 | 00 | Módulo salidas inventario | ALTA | DONE | - | Admin page existe |
| REQ-INV-03 | 00 | Módulo ajustes inventario | ALTA | DONE | - | Admin page existe |
| REQ-INV-04 | 00 | Kardex | ALTA | DONE | - | Admin page existe |
| REQ-INV-05 | 00 | Trazabilidad completa (schema) | ALTA | DONE | - | Tabla verificada |
| REQ-INV-07 | 00 | WC fuente única stock | ALTA | DONE | - | `set_stock_quantity()` |
| REQ-INV-08 | 00 | Validación salida > stock | ALTA | DONE | - | Doble capa validación |
| REQ-ROL-01 | 00 | Rol administración existe | ALTA | DONE | - | `wp_capabilities` admin |
| REQ-ROL-04 | 00 | Capacidad manage_tanish_inventory | ALTA | DONE | - | Asignada a admin/shop_manager |
| REQ-SEG-04 | 00 | Nonces en formularios | ALTA | DONE | - | `check_admin_referer()` |
| REQ-SEG-05 | 00 | Capabilities por rol | ALTA | DONE | - | `current_user_can()` |
| REQ-SEG-06 | 00 | Sanitización inputs | ALTA | DONE | - | `absint()`, `sanitize_*()` |
| REQ-SEG-07 | 00 | Escaping outputs | ALTA | DONE | - | `esc_html()`, `esc_url()` |
| REQ-SEG-08 | 00 | MySQL privado | ALTA | DONE | - | Sin port mapping |
| REQ-SEG-09 | 00 | .env ignorado | ALTA | DONE | - | `.gitignore` |
| REQ-SEG-10 | 00 | Prepared statements | ALTA | DONE | - | `$wpdb->prepare()` |
| REQ-ECO-05 | 01 | Carrito funcional | CRITICA | NOT STARTED | Sprint 00 | |
| REQ-ECO-06 | 01 | Checkout funcional | CRITICA | NOT STARTED | Sprint 00 | |
| REQ-ECO-07 | 01 | Creación de pedidos | CRITICA | NOT STARTED | REQ-ECO-06 | |
| REQ-ECO-08 | 01 | Stock tras pedido | ALTA | NOT STARTED | REQ-ECO-07 | |
| REQ-ECO-09 | 01 | Flujo B2C completo | CRITICA | NOT STARTED | REQ-ECO-05, REQ-ECO-06 | |
| REQ-INV-06 | 02 | Movimientos de demostración | ALTA | NOT STARTED | - | |
| REQ-ROL-02 | 02 | Rol Almacén | ALTA | NOT STARTED | - | |
| REQ-ROL-03 | 02 | Rol Ventas | ALTA | NOT STARTED | - | |
| REQ-ANA-01 | 03 | GA4 instalado | ALTA | NOT STARTED | - | |
| REQ-ANA-02 | 03 | Evento view_item | MEDIA | NOT STARTED | REQ-ANA-01 | |
| REQ-ANA-03 | 03 | Evento add_to_cart | MEDIA | NOT STARTED | REQ-ANA-01, REQ-ECO-05 | |
| REQ-ANA-04 | 03 | Evento begin_checkout | MEDIA | NOT STARTED | REQ-ANA-01, REQ-ECO-06 | |
| REQ-ANA-05 | 03 | Evento purchase | MEDIA | NOT STARTED | REQ-ANA-01, REQ-ECO-07 | |
| REQ-ANA-06 | 03 | Evento click_whatsapp | MEDIA | NOT STARTED | REQ-ANA-01 | |
| REQ-ANA-07 | 03 | Google Search Console | BAJA | BLOCKED | Cuenta Google | |
| REQ-SEG-01 | 04 | HTTPS producción | ALTA | PARTIAL | VPS + dominio | Config preparada en docker-compose.prod.yml + Caddyfile |
| REQ-SEG-02 | 04 | MFA administradores | ALTA | NOT STARTED | Plugin externo | |
| REQ-SEG-03 | 04 | Backups automáticos | ALTA | PARTIAL | Cron host | Script manual existe, falta automatización |
| REQ-PRU-01 | 05 | Pruebas unitarias PHPUnit | MEDIA | NOT STARTED | Sprints 01-04 | |
| REQ-PRU-02 | 05 | UAT 12 casos | MEDIA | NOT STARTED | Sprints 01-04 | |
| REQ-PRU-03 | 05 | Smoke tests HTTP | MEDIA | NOT STARTED | Sprint 01 | |
| REQ-ACA-01 | 06 | Business Case | MEDIA | NOT STARTED | Todos sprints | |
| REQ-ACA-02 | 06 | Flujograma | MEDIA | NOT STARTED | Sprint 01 | |
| REQ-ACA-03 | 06 | Cronograma/EDT | MEDIA | NOT STARTED | Todos sprints | |
| REQ-ACA-04 | 06 | Plan de Riesgos | MEDIA | NOT STARTED | Todos sprints | |
| REQ-ACA-05 | 06 | Interesados | MEDIA | NOT STARTED | - | |
| REQ-ACA-06 | 06 | Presupuesto | MEDIA | NOT STARTED | - | |
| REQ-ACA-07 | 06 | Plan de Seguridad | MEDIA | NOT STARTED | Sprint 04 | |
| REQ-ACA-08 | 06 | Plan de Calidad | MEDIA | NOT STARTED | Sprint 05 | |
| REQ-ACA-09 | 06 | Metodología | MEDIA | NOT STARTED | - | |
| REQ-ACA-10 | 06 | Despliegue | MEDIA | NOT STARTED | Sprint 01 | |
| REQ-ACA-11 | 06 | Resultados | MEDIA | NOT STARTED | Todos sprints | |
| REQ-ACA-12 | 06 | Conclusiones | MEDIA | NOT STARTED | Todos sprints | |
| REQ-VER-01 | 06 | Versiones reales documentadas | ALTA | DONE | - | SPRINT-00-BASELINE.md |

## Resumen de Estado

| Estado | Cantidad |
|---|---|
| DONE | 22 |
| PARTIAL | 2 |
| NOT STARTED | 30 |
| BLOCKED | 1 |
| **Total** | **55** |

## Bloqueos Activos

| ID | Requisito | Recurso faltante | Acción posible |
|---|---|---|---|
| REQ-ANA-07 | Google Search Console | Cuenta Google + propiedad | Preparar infraestructura (sitemap, robots) |
