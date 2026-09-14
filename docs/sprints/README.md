# Roadmap de Sprints — TANISH E-Commerce

## Objetivo

Convertir las brechas detectadas en la auditoría técnica en un plan de desarrollo estructurado por sprints, para alinear el sistema TANISH con el documento académico "E-BUSINESS Y ANALÍTICA WEB - 226434.19595 (Presencial).docx".

## Estado Actual (antes de sprints)

| Componente | Estado |
|---|---|
| WordPress 7.1 + WooCommerce 11.0.1 | IMPLEMENTADO |
| TANISH Inventory 0.3.0 (entradas/salidas/ajustes/kardex) | IMPLEMENTADO |
| TANISH Storefront 2.0.0 | IMPLEMENTADO |
| Catálogo (12 productos, 15 categorías) | IMPLEMENTADO |
| WhatsApp como canal de venta | IMPLEMENTADO |
| Docker + MySQL 8.0 | IMPLEMENTADO |
| Carrito funcional | NO IMPLEMENTADO |
| Checkout funcional | NO IMPLEMENTADO |
| Creación de pedidos WooCommerce | NO IMPLEMENTADO |
| GA4 / analytics | NO IMPLEMENTADO |
| MFA | NO IMPLEMENTADO |
| Roles diferenciados (almacén/ventas) | NO IMPLEMENTADO |
| Backups automáticos | NO IMPLEMENTADO |
| HTTPS desplegado | PREPARADO (no activo) |
| Pruebas unitarias / UAT | NO IMPLEMENTADO |

## Metodología

Desarrollo iterativo organizado en sprints académicos. Cada sprint:
1. Tiene un objetivo claro vinculado a un OE del documento
2. Produce evidencia verificable
3. Se clasifica como DONE / PARTIAL / BLOCKED / NOT STARTED
4. No se marca DONE sin evidencia real

## Definición de DONE

Un requisito está DONE cuando:
- Existe código implementado y funcionando
- Se ha verificado en runtime (localhost:8080)
- Se ha documentado la evidencia
- Los criterios de aceptación se cumplen
- No depende de infraestructura externa

## Definición de BLOCKED

Un requisito está BLOCKED cuando:
- Requiere un recurso externo no disponible (VPS, dominio, Measurement ID, cuenta Google)
- No puede resolverse localmente
- Se documenta qué recurso falta y qué se hizo internamente

## Orden de Sprints y Dependencias

```
Sprint 00 (Baseline) ─────────────────────────────────┐
                                                        │
Sprint 01 (B2C Commerce) ← depende de Sprint 00 ──────┤
                                                        │
Sprint 02 (Roles + Inventario) ← independiente ────────┤
                                                        │
Sprint 03 (Analytics) ← independiente ─────────────────┤
                                                        │
Sprint 04 (Security) ← independiente ──────────────────┤
                                                        │
Sprint 05 (Testing) ← depende de 01, 02, 03, 04 ──────┤
                                                        │
Sprint 06 (Academic) ← depende de todos ───────────────┘
```

## Tabla de Sprints

| Sprint | Objetivo | Requisitos académicos | Estado | Dependencias | Evidencia | Resultado esperado |
|---|---|---|---|---|---|---|
| Sprint 00 | Baseline y trazabilidad | Preparación | PLANNED | Ninguna | Snapshot técnico | Estado actual documentado |
| Sprint 01 | Comercio B2C WooCommerce | OE1 (catálogo, carrito, checkout) | PLANNED | Sprint 00 | Carrito, checkout, order ID | Flujo B2C funcional + WhatsApp |
| Sprint 02 | Roles + Inventario | OE2, OE3 | PLANNED | Ninguna | Roles, movimientos, kardex | Perfiles diferenciados + evidencia inventario |
| Sprint 03 | Analítica web | OE4 | PLANNED | Ninguna | GA4 config, eventos | Infraestructura GA4 (BLOCKED sin ID) |
| Sprint 04 | Seguridad y continuidad | OE5 | PLANNED | Ninguna | MFA, backups, HTTPS | Controles de seguridad |
| Sprint 05 | Pruebas y evidencias | 6.2 Pruebas | PLANNED | Sprints 01-04 | Tests, UAT | Suite de pruebas + UAT |
| Sprint 06 | Alineación académica | Secciones 5.7-8 | PLANNED | Todos los sprints | Documentos académicos | Contenido listo para informe |

## Relación con Auditoría

La auditoría técnica de alineación (AUDITORÍA DE ALINEACIÓN DOCUMENTO ACADÉMICO ↔ SISTEMA TANISH) identificó 46+ requisitos con estado INCONSISTENTE, NO IMPLEMENTADO o PARCIAL. Cada sprint aborda un grupo de estos requisitos.

Ver `TRACEABILITY.md` para la matriz completa de trazabilidad.

## Archivos del Roadmap

| Archivo | Contenido |
|---|---|
| `README.md` | Este archivo — roadmap general |
| `TRACEABILITY.md` | Matriz central de trazabilidad |
| `SPRINT-BOARD.md` | Tablero kanban |
| `SPRINT-00-BASELINE.md` | Estado actual congelado |
| `SPRINT-01-B2C-COMMERCE.md` | Carrito + Checkout + Pedidos |
| `SPRINT-02-ROLES-INVENTORY.md` | Roles + Movimientos demo |
| `SPRINT-03-ANALYTICS.md` | GA4 + Eventos |
| `SPRINT-04-SECURITY.md` | MFA + Backups + HTTPS |
| `SPRINT-05-TESTING-EVIDENCE.md` | Pruebas + UAT + Evidencias |
| `SPRINT-06-ACADEMIC-ALIGNMENT.md` | Documentos académicos |
