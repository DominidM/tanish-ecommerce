# SPRINT 05 — Pruebas y Evidencias

## Nombre del Sprint
Sprint 05: Pruebas y Evidencias

## Objetivo
Crear suite de pruebas (unitarias, smoke, UAT) y documentar evidencias del sistema completo.

## Justificación Académica
- Sección 6.2: "Pruebas de calidad"
- Sección 6.2.1: "Pruebas unitarias"
- Sección 6.2.2: "Pruebas de aceptación"
- Tabla 2: Indicadores de calidad

## Secciones del Documento Relacionadas
- 6.2: Pruebas de calidad
- 6.2.1: Pruebas unitarias
- 6.2.2: Pruebas de aceptación

## Estado Inicial
- Sin directorio `tests/`
- Sin PHPUnit configurado
- Sin casos de prueba documentados
- Sin evidencias formales
- `docs/pruebas/` y `docs/evidencias/` existen pero vacíos (.gitkeep)

## Historias de Usuario

### HU-22: Como desarrollador, quiero pruebas unitarias para la lógica de inventario
**Criterio:** Tests para entrada, salida, salida > stock, ajuste.

### HU-23: Como desarrollador, quiero smoke tests que verifiquen las páginas principales
**Criterio:** HTTP 200 en Home, Shop, Cart, Checkout, Product, Login.

### HU-24: Como tester, quiero casos UAT documentados para validación funcional
**Criterio:** 12 casos de prueba con precondición, pasos, resultado esperado/obtenido.

### HU-25: Como administrador, quiero un índice de evidencias
**Criterio:** `docs/evidencias/README.md` con lista de evidencia generada.

## Requisitos Funcionales
1. Directorio `tests/` con estructura unit/integration/smoke
2. Al menos 5 pruebas unitarias para TANISH Inventory Service
3. Smoke tests para 7 páginas principales
4. 12 casos UAT documentados
5. Índice de evidencias

## Requisitos No Funcionales
- No fingir pruebas con curl
- Pruebas reales ejecutables
- UAT basado en funcionalidad real del sistema
- Evidencia referenciada, no inventada

## Tareas Técnicas

### T-34: Crear estructura de tests
**Directorio:** `tests/`
**Estructura:**
```
tests/
├── unit/
│   └── test-inventory-service.php
├── integration/
│   └── test-database-operations.php
└── smoke/
    └── test-pages.php
```

### T-35: Crear pruebas unitarias para Inventory Service
**Clase a probar:** `Tanish_Inventory_Service`
**Métodos a probar:**
- `apply_movement('entry')` → stock increase
- `apply_movement('exit')` → stock decrease
- `apply_movement('exit', qty > stock)` → WP_Error insufficient_stock
- `apply_movement('adjustment')` → stock set to absolute value
- `apply_movement('invalid')` → WP_Error invalid_movement
- `get_dashboard_summary()` → counts correctos

**Framework:** PHPUnit con WordPress test suite si es posible, o tests PHP standalone

### T-36: Crear smoke tests HTTP
**Acción:** Script bash o PHP que verifica HTTP 200 en:
- `http://localhost:8080/` (Home)
- `http://localhost:8080/shop/` (Shop)
- `http://localhost:8080/cart/` (Cart)
- `http://localhost:8080/checkout/` (Checkout) — puede ser 302
- `http://localhost:8080/wp-login.php` (Login)
- `http://localhost:8080/product/arroz-extra-1-kg/` (Producto)
- `http://localhost:8080/nosotros/` (Nosotros)

### T-37: Crear casos UAT
**Archivo:** `docs/pruebas/UAT.md`
**Casos:**
- UAT-01: Catálogo muestra productos
- UAT-02: Carrito funcional
- UAT-03: Checkout funcional
- UAT-04: WhatsApp funciona
- UAT-05: Entrada inventario
- UAT-06: Salida inventario
- UAT-07: Ajuste inventario
- UAT-08: Kardex muestra movimientos
- UAT-09: Roles diferenciados
- UAT-10: Analytics configurado
- UAT-11: Banners activos
- UAT-12: Videos activos

### T-38: Crear índice de evidencias
**Archivo:** `docs/evidencias/README.md`
**Contenido:** Lista de evidencia generada por sprint, con comandos y outputs

### T-39: Ejecutar y documentar pruebas
**Acción:** Ejecutar cada prueba y guardar resultado
**Archivos:** `docs/evidencias/sprint-05/`

## Criterios de Aceptación
- [ ] Directorio `tests/` creado con estructura
- [ ] Al menos 5 pruebas unitarias escritas
- [ ] Smoke tests ejecutables
- [ ] 12 casos UAT documentados
- [ ] Índice de evidencias creado
- [ ] Pruebas ejecutadas y resultado documentado

## Pruebas
1. Ejecutar pruebas unitarias → todas pasan
2. Ejecutar smoke tests → HTTP 200 en páginas principales
3. Revisar UAT → casos completos y realistas
4. Verificar índice de evidencias → completo

## Evidencias Requeridas
- Output de pruebas unitarias
- Output de smoke tests
- Contenido de UAT.md
- Índice de evidencias

## Riesgos
- R-13: PHPUnit no disponible → usar tests PHP standalone
- R-14: WordPress test suite compleja → simplificar pruebas
- R-15: UAT incompleto → documentar solo lo verificable

## Dependencias
- Sprint 01 (para probar carrito/checkout)
- Sprint 02 (para probar inventario/roles)
- Sprint 03 (para probar analytics)
- Sprint 04 (para probar seguridad)

## Definition of Done
- [ ] `tests/` creado con estructura
- [ ] Pruebas unitarias escritas y pasan
- [ ] Smoke tests ejecutados
- [ ] UAT documentado (12 casos)
- [ ] Índice de evidencias creado
- [ ] Todo documentado en `docs/evidencias/sprint-05/`

## Estado Final
**STATUS: PLANNED**

## Archivos Modificados (planificados)
- `tests/` (nuevo directorio)
- `tests/unit/test-inventory-service.php` (nuevo)
- `tests/smoke/test-pages.php` (nuevo)
- `docs/pruebas/UAT.md` (nuevo)
- `docs/evidencias/README.md` (nuevo)
- `docs/evidencias/sprint-05/` (nuevo directorio)

## Resultado Esperado
Suite de pruebas real con evidencia verificable. UAT completo. Documentación de calidad.

## Brechas Restantes
- Pruebas unitarias dependen de entorno PHP/Docker
- UAT requiere ejecución manual con múltiples usuarios
- Evidencia visual (screenshots) depende del usuario
