# SPRINT 02 — Roles + Inventario + Trazaibilidad

## Nombre del Sprint
Sprint 02: Roles, Inventario y Trazaibilidad

## Objetivo
Completar los perfiles de acceso diferenciados (OE3) y generar evidencia de movimientos de inventario (OE2).

## Justificación Académica
- OE2: "Desarrollar un módulo de entradas, salidas y ajustes de inventario que no requieran boleta o factura"
- OE3: "Configurar perfiles de acceso diferenciados para administración, almacén y ventas"
- Tabla 2: "Porcentaje de movimientos con usuario, motivo y saldo posterior registrados"

## Secciones del Documento Relacionadas
- 3.2.2 Problema específico 3: "falta de perfiles de acceso diferenciados"
- 3.4.2 OE2: módulo de inventario
- 3.4.2 OE3: perfiles diferenciados
- Abstract: "trazabilidad completa (usuario, fecha, motivo, stock anterior y posterior)"

## Estado Inicial
- Plugin TANISH Inventory funcional (entradas/salidas/ajustes/kardex)
- Tabla `wp_tanish_inventory_movements` con schema correcto pero 0 registros
- Solo 1 usuario (tanish_admin) con rol administrator
- Capacidad `manage_tanish_inventory` asignada a administrator y shop_manager
- No existen roles "almacén" ni "ventas"

## Historias de Usuario

### HU-07: Como administrador, quiero crear un usuario con rol "Almacén" que solo pueda gestionar inventario
**Criterio:** Usuario con rol almacén ve dashboard inventario, entradas, salidas, ajustes, kardex. No ve configuración global.

### HU-08: Como administrador, quiero crear un usuario con rol "Ventas" que pueda ver productos y pedidos
**Criterio:** Usuario con rol ventas ve productos y pedidos. No ve ajustes sensibles de inventario.

### HU-09: Como almacén, quiero registrar una entrada de inventario con trazabilidad
**Criterio:** Movimiento registrado con user_id, fecha, motivo, stock_before, stock_after.

### HU-10: Como almacén, quiero registrar una salida de inventario validando stock
**Criterio:** Si cantidad > stock, se muestra error. Si no, se registra movimiento.

### HU-11: Como almacén, quiero ajustar el stock de un producto
**Criterio:** Ajuste registra stock anterior y nuevo stock.

### HU-12: Como administrador, quiero ver el kardex de un producto
**Criterio:** Kardex muestra historial cronológico con entradas, salidas, ajustes.

## Requisitos Funcionales
1. Rol "Almacén" con capabilities de inventario
2. Rol "Ventas" con capabilities de productos/pedidos
3. Administrator conserva acceso completo
4. Al menos 3 movimientos de demostración (entrada/salida/ajuste)
5. Movimientos con campos completos: product_id, movement_type, quantity_change, stock_before, stock_after, reason, user_id, created_at
6. Kardex muestra movimientos correctamente

## Requisitos No Funcionales
- No romper rol administrator existente
- shop_manager sigue funcionando
- Mínimo privilegio: almacén no ve config global, ventas no ve ajustes sensibles
- Movimientos de prueba no se eliminan (evidencia permanente)

## Tareas Técnicas

### T-10: Crear roles personalizados
**Archivo:** `plugins/tanish-inventory/includes/class-tanish-inventory-capabilities.php`
**Acción:** Agregar método para crear roles `tanish_warehouse` y `tanish_sales` con capabilities específicas
**Capabilities almacén:** read, manage_tanish_inventory, edit_products (solo lectura), view_inventory_reports
**Capabilities ventas:** read, edit_products, manage_woocommerce, view_inventory_reports (sin manage_tanish_inventory completo)
**NO romper:** administrator existente

### T-11: Crear usuario de prueba "almacen"
**Acción:** Crear usuario WordPress con rol tanish_warehouse
**Credenciales:** Documentar en evidencia, no en código

### T-12: Crear usuario de prueba "ventas"
**Acción:** Crear usuario WordPress con rol tanish_sales
**Credenciales:** Documentar en evidencia, no en código

### T-13: Ejecutar movimientos de demostración
**Acción:** Usar plugin TANISH Inventory para crear 3 movimientos
**Producto:** Elegir producto existente (ej: "Arroz Extra 1 kg", stock actual = 30)
**Movimientos:**
1. Entrada: +2 (stock: 30 → 32)
2. Salida: -1 (stock: 32 → 31)
3. Ajuste: volver a 30 (stock: 31 → 30)

**Motivos:**
- "Prueba académica Sprint 02 - Entrada"
- "Prueba académica Sprint 02 - Salida"
- "Prueba académica Sprint 02 - Ajuste"

### T-14: Verificar trazabilidad de movimientos
**Acción:** Consultar tabla `wp_tanish_inventory_movements`
**Verificar:** Todos los campos presentes y correctos para cada movimiento
**Campos:** id, product_id, movement_type, quantity_change, stock_before, stock_after, reason, user_id, created_at

### T-15: Verificar kardex
**Acción:** Visitar `wp-admin/admin.php?page=tanish-inventory-kardex`
**Verificar:** Movimientos aparecen en orden cronológico con tipo, cantidades, saldos

### T-16: Verificar dashboard inventario
**Acción:** Visitar `wp-admin/admin.php?page=tanish-inventory`
**Verificar:** Resumen de productos, stock, movimientos recientes

### T-17: Documentar evidencia
**Acción:** Guardar outputs de cada verificación
**Archivos:** `docs/evidencias/sprint-02/`

## Criterios de Aceptación
- [ ] Rol "Almacén" creado y funcional
- [ ] Rol "Ventas" creado y funcional
- [ ] Administrator conserva acceso completo
- [ ] Al menos 3 movimientos registrados
- [ ] Cada movimiento tiene: product_id, movement_type, quantity_change, stock_before, stock_after, reason, user_id, created_at
- [ ] Kardex muestra movimientos correctamente
- [ ] Dashboard inventario muestra resumen
- [ ] Evidencia documentada

## Pruebas
1. Login como administrador → acceso completo
2. Login como almacén → ver solo inventario
3. Login como ventas → ver productos/pedidos
4. Registrar entrada desde almacén → verificar movimiento
5. Registrar salida desde almacén → verificar validación stock
6. Registrar ajuste desde almacén → verificar movimiento
7. Verificar kardex del producto
8. Verificar dashboard

## Evidencias Requeridas
- Lista de roles creados con capabilities
- Usuarios de prueba creados
- 3 movimientos en `wp_tanish_inventory_movements`
- Output de kardex
- Screenshot de dashboard inventario
- Output de verificación de trazabilidad

## Riesgos
- R-04: Roles no se crean correctamente → verificar con `get_role()` en PHP
- R-05: Movimientos fallan → usar forms del plugin directamente
- R-06: Admin pierde acceso → testing crítico

## Dependencias
Ninguna — independiente de Sprint 01.

## Definition of Done
- [ ] Roles creados y probados
- [ ] Movimientos de demostración ejecutados
- [ ] Trazabilidad verificada
- [ ] Kardex funcional
- [ ] Evidencia documentada

## Estado Final
**STATUS: PLANNED**

## Archivos Modificados (planificados)
- `plugins/tanish-inventory/includes/class-tanish-inventory-capabilities.php` (agregar roles)
- `docs/evidencias/sprint-02/` (nuevo directorio)

## Resultado Esperado
Perfiles diferenciados operativos + evidencia de trazabilidad de inventario con movimientos reales.

## Brechas Restantes
- Roles creados pero no probados con múltiples usuarios simultáneamente
- Movimientos son de prueba, no de uso real del negocio
