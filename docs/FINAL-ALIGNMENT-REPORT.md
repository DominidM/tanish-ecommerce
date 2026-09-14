# FINAL-ALIGNMENT-REPORT.md — Informe de Alineación: Estado Inicial

## Propósito

Este documento registra el **estado inicial** del sistema TANISH antes de ejecutar los sprints de desarrollo. sirve como línea base para medir el impacto de cada sprint.

El **estado final** se actualizará después de ejecutar los sprints correspondientes.

## Resumen Ejecutivo — Estado Inicial

| Métrica | Valor |
|---|---|
| Total requisitos rastreados | 55 |
| Requisitos DONE (pre-sprints) | 22 |
| Requisitos PARTIAL | 2 |
| Requisitos BLOCKED | 1 |
| Requisitos NOT STARTED | 30 |
| Sprint plan creado | 7 documentos |
| Documentos académicos creados | 14 archivos |
| Scripts de operación | 3 scripts |

## Estado Inicial por Requisito

| ID | Requisito | Estado Inicial | Sprint Responsable | Estado Final |
|---|---|---|---|---|
| REQ-ECO-01 | 12 productos publicados con precios y stock | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-02 | 15 categorías configuradas | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-03 | Todos los productos tienen precio | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-04 | Stock gestionado por WooCommerce | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-05 | Página /cart/ funcional con add_to_cart | NOT STARTED | Sprint 01 | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-06 | Formulario WooCommerce en /checkout/ | NOT STARTED | Sprint 01 | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-07 | Pedido WooCommerce generado tras checkout | NOT STARTED | Sprint 01 | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-08 | WooCommerce descuenta stock al confirmar pedido | NOT STARTED | Sprint 01 | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-09 | Flujo B2C completo: catálogo → producto → carrito → checkout → pedido | NOT STARTED | Sprint 01 | [PENDIENTE POST-SPRINTS] |
| REQ-ECO-10 | Canal WhatsApp coexistente con B2C | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-01 | Plugin registra entradas de inventario | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-02 | Plugin registra salidas de inventario | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-03 | Plugin registra ajustes de stock | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-04 | Historial cronológico de movimientos (kárdex) | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-05 | Trazabilidad completa: user, fecha, motivo, stock_before, stock_after | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-06 | Al menos 3 movimientos de demostración (entrada/salida/ajuste) | NOT STARTED | Sprint 02 | [PENDIENTE POST-SPRINTS] |
| REQ-INV-07 | Plugin actualiza stock vía WC API (fuente única) | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-INV-08 | Bloqueo cuando salida excede stock | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ROL-01 | Rol administración con acceso completo | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ROL-02 | Rol Almacén: acceso a inventario, sin config global | NOT STARTED | Sprint 02 | [PENDIENTE POST-SPRINTS] |
| REQ-ROL-03 | Rol Ventas: productos, pedidos, consulta stock | NOT STARTED | Sprint 02 | [PENDIENTE POST-SPRINTS] |
| REQ-ROL-04 | Capacidad manage_tanish_inventory asignada a roles apropiados | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-01 | GA4 instalado: gtag.js o gtag config en frontend | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-02 | Evento view_item al ver producto individual | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-03 | Evento add_to_cart al añadir al carrito | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-04 | Evento begin_checkout al iniciar checkout | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-05 | Evento purchase al completar compra | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-06 | Evento click_whatsapp: tracking de clicks en WhatsApp | NOT STARTED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-ANA-07 | Google Search Console: verificación + sitemap | BLOCKED | Sprint 03 | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-01 | TLS activo en producción (HTTPS) | PARTIAL | Sprint 04 | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-02 | Autenticación multifactor para administradores | NOT STARTED | Sprint 04 | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-03 | Respaldo automático de base de datos | PARTIAL | Sprint 04 | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-04 | Nonces en formularios | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-05 | Capabilities por rol (control de acceso) | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-06 | Sanitización de inputs | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-07 | Escaping de outputs (protección XSS) | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-08 | MySQL privado (puerto no expuesto) | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-09 | .env excluido de Git | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-SEG-10 | Prepared statements SQL | DONE | — | [PENDIENTE POST-SPRINTS] |
| REQ-PRU-01 | Pruebas unitarias (PHPUnit) | NOT STARTED | Sprint 05 | [PENDIENTE POST-SPRINTS] |
| REQ-PRU-02 | 12 casos UAT documentados | NOT STARTED | Sprint 05 | [PENDIENTE POST-SPRINTS] |
| REQ-PRU-03 | Smoke tests HTTP 200 | NOT STARTED | Sprint 05 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-01 | Documento 5.7 Business Case completo | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-02 | Documento 5.8 Flujograma con diagrama | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-03 | Documento 5.9 EDT + cronograma | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-04 | Documento 5.10 Matriz de riesgos | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-05 | Documento 5.11 Registro de interesados | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-06 | Documento 5.12 Presupuesto | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-07 | Documento 5.13 Plan de seguridad | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-08 | Documento 5.14 Plan de calidad | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-09 | Documento 6 Metodología | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-10 | Documento 6.3 Despliegue | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-11 | Documento 7 Resultados (template) | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-ACA-12 | Documento 8 Conclusiones (template) | NOT STARTED | Sprint 06 | [PENDIENTE POST-SPRINTS] |
| REQ-VER-01 | Versiones reales documentadas | DONE | — | [PENDIENTE POST-SPRINTS] |

## Archivos de Sprint — Estado

| Archivo | Estado |
|---|---|
| `docs/sprints/README.md` | CREADO |
| `docs/sprints/TRACEABILITY.md` | CREADO |
| `docs/sprints/SPRINT-BOARD.md` | CREADO |
| `docs/sprints/SPRINT-00-BASELINE.md` | CREADO |
| `docs/sprints/SPRINT-01-B2C-COMMERCE.md` | CREADO |
| `docs/sprints/SPRINT-02-ROLES-INVENTORY.md` | CREADO |
| `docs/sprints/SPRINT-03-ANALYTICS.md` | CREADO |
| `docs/sprints/SPRINT-04-SECURITY.md` | CREADO |
| `docs/sprints/SPRINT-05-TESTING-EVIDENCE.md` | CREADO |
| `docs/sprints/SPRINT-06-ACADEMIC-ALIGNMENT.md` | CREADO |

## Documentos Académicos — Estado

| Archivo | Estado |
|---|---|
| `docs/academic/5.7-business-case.md` | CREADO |
| `docs/academic/5.8-flujograma.md` | CREADO |
| `docs/academic/5.9-edt.md` | CREADO |
| `docs/academic/5.9-cronograma.md` | CREADO |
| `docs/academic/5.10-riesgos.md` | CREADO |
| `docs/academic/5.11-interesados.md` | CREADO |
| `docs/academic/5.12-presupuesto.md` | CREADO |
| `docs/academic/5.13-seguridad.md` | CREADO |
| `docs/academic/5.14-calidad.md` | CREADO |
| `docs/academic/6-metodologia.md` | CREADO |
| `docs/academic/6.2-pruebas.md` | CREADO |
| `docs/academic/6.3-despliegue.md` | CREADO |
| `docs/academic/7-resultados-template.md` | CREADO (template) |
| `docs/academic/8-conclusiones-template.md` | CREADO (template) |

## Scripts — Estado

| Script | Estado |
|---|---|
| `scripts/backup.sh` | CREADO |
| `scripts/restore.sh` | CREADO |
| `scripts/deploy-production.sh` | CREADO |

## Próximos Pasos

1. **Revisar** esta documentación con el equipo
2. **Aprobar** plan de sprints
3. **Ejecutar** Sprint 01 (B2C Commerce)
4. **Ejecutar** Sprint 02 (Roles + Inventario)
5. **Ejecutar** Sprint 03 (Analytics)
6. **Ejecutar** Sprint 04 (Security)
7. **Ejecutar** Sprint 05 (Testing)
8. **Ejecutar** Sprint 06 (Academic docs)
9. **Actualizar** Estado Final en este documento tras cada sprint
10. **Commit + Push** después de aprobación del usuario
