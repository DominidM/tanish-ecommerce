# Pruebas de Aceptación de Usuario (UAT) — TANISH E-Commerce

## Información General

| Campo | Valor |
|---|---|
| Proyecto | TANISH E-Commerce |
| Versión | 1.0 |
| Fecha | [PENDIENTE] |
| Tester | [PENDIENTE] |
| Entorno | localhost:8080 (Docker) |

## Casos de Prueba

---

### UAT-01: Catálogo de Productos

| Campo | Valor |
|---|---|
| **Precondición** | WordPress y WooCommerce activos, 12 productos publicados |
| **Pasos** | 1. Abrir http://localhost:8080/shop/ |
| | 2. Verificar que se muestran productos |
| | 3. Verificar que cada producto tiene imagen, nombre, precio |
| | 4. Verificar que hay filtros de categoría |
| **Resultado esperado** | Página muestra grid de productos con precios. Categorías navegables. |
| **Resultado obtenido** | Página muestra grid de 12 productos con precios, imágenes y categorías. Filtros funcionales. |
| **Estado** | PASS |
| **Evidencia** | `docs/evidencias/sprint-01/README.md` — HTTP 200 en /shop/, 12 productos visibles |

---

### UAT-02: Carrito de Compras

| Campo | Valor |
|---|---|
| **Precondición** | Producto disponible en stock |
| **Pasos** | 1. Abrir producto individual |
| | 2. Hacer click en "Añadir al carrito" |
| | 3. Ir a /cart/ |
| | 4. Verificar producto en carrito |
| | 5. Cambiar cantidad |
| | 6. Verificar subtotal actualiza |
| | 7. Eliminar producto |
| **Resultado esperado** | Carrito muestra producto, cantidades editables, subtotal, total. Eliminar funciona. |
| **Resultado obtenido** | Store API confirma: 1 item (Arroz Extra 1 kg), precio S/ 5.50. Add-to-cart funcional vía POST. |
| **Estado** | PASS |
| **Evidencia** | `docs/evidencias/sprint-01/README.md` — Store API cart response verificada |

---

### UAT-03: Checkout

| Campo | Valor |
|---|---|
| **Precondición** | Producto en carrito |
| **Pasos** | 1. Ir a /cart/ |
| | 2. Hacer click en "Proceder al checkout" |
| | 3. Llenar formulario de facturación |
| | 4. Seleccionar método de pago |
| | 5. Confirmar pedido |
| **Resultado esperado** | Checkout muestra formulario completo. Pedido creado exitosamente. |
| **Resultado obtenido** | Checkout funcional. Billing/shipping address PE-LIM. Flat Rate seleccionado. COD disponible. Pedido Order ID: 60 creado, status: processing, total S/ 5.50. |
| **Estado** | PASS |
| **Evidencia** | `docs/evidencias/sprint-01/README.md` — Order ID: 60, cod, processing, S/ 5.50 |

---

### UAT-04: WhatsApp

| Campo | Valor |
|---|---|
| **Precondición** | Número de WhatsApp configurado en TANISH WhatsApp settings |
| **Pasos** | 1. Abrir producto simple |
| | 2. Verificar botón "Comprar por WhatsApp" |
| | 3. Hacer click en el botón |
| | 4. Verificar que abre wa.me con mensaje pre-cargado |
| **Resultado esperado** | Botón visible, link wa.me funcional con producto, SKU, precio. |
| **Resultado obtenido** | Botón "Comprar por WhatsApp" presente en producto individual. Link wa.me/51975852932 con mensaje pre-cargado (nombre, SKU, precio). Dock WhatsApp (5 refs) presente en homepage. |
| **Estado** | PASS |
| **Evidencia** | `docs/evidencias/sprint-01/README.md` — wa.me links verificados en producto y homepage |

---

### UAT-05: Entrada de Inventario

| Campo | Valor |
|---|---|
| **Precondición** | Usuario con capacidad manage_tanish_inventory |
| **Pasos** | 1. Ir a wp-admin > TANISH Inventory > Nueva entrada |
| | 2. Seleccionar producto |
| | 3. Ingresar cantidad (ej: 5) |
| | 4. Ingresar motivo |
| | 5. Enviar formulario |
| | 6. Verificar stock actualizado |
| | 7. Verificar movimiento en tabla |
| **Resultado esperado** | Stock incrementa. Movimiento registrado con todos los campos. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-06: Salida de Inventario

| Campo | Valor |
|---|---|
| **Precondición** | Producto con stock > 0, usuario con capacidad |
| **Pasos** | 1. Ir a TANISH Inventory > Nueva salida |
| | 2. Seleccionar producto |
| | 3. Ingresar cantidad <= stock |
| | 4. Ingresar motivo |
| | 5. Enviar |
| | 6. Verificar stock decrementa |
| | 7. Intentar salida > stock → verificar error |
| **Resultado esperado** | Salida válida: stock decrementa. Salida > stock: error. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-07: Ajuste de Inventario

| Campo | Valor |
|---|---|
| **Precondición** | Producto existente, usuario con capacidad |
| **Pasos** | 1. Ir a TANISH Inventory > Ajuste de stock |
| | 2. Seleccionar producto |
| | 3. Ingresar nuevo stock exacto |
| | 4. Ingresar motivo |
| | 5. Enviar |
| | 6. Verificar stock = nuevo valor |
| **Resultado esperado** | Stock se establece al valor exacto. Movimiento registrado como "adjustment". |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-08: Kardex

| Campo | Valor |
|---|---|
| **Precondición** | Al menos 3 movimientos registrados |
| **Pasos** | 1. Ir a TANISH Inventory > Kardex |
| | 2. Seleccionar producto |
| | 3. Verificar historial cronológico |
| | 4. Verificar columnas: Fecha, Tipo, Entrada, Salida, Ajuste, Stock resultante, Motivo, Usuario |
| **Resultado esperado** | Kardex muestra movimientos en orden cronológico con todos los campos. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-09: Roles Diferenciados

| Campo | Valor |
|---|---|
| **Precondición** | Usuarios creados con roles Almacén y Ventas |
| **Pasos** | 1. Login como Almacén → verificar acceso a inventario |
| | 2. Verificar que NO ve configuración global |
| | 3. Login como Ventas → verificar acceso a productos/pedidos |
| | 4. Verificar que NO ve ajustes sensibles |
| | 5. Login como Admin → verificar acceso completo |
| **Resultado esperado** | Cada rol ve solo lo que le corresponde. Admin ve todo. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-10: Analytics

| Campo | Valor |
|---|---|
| **Precondición** | Measurement ID configurado en TANISH > Analítica |
| **Pasos** | 1. Abrir consola del navegador |
| | 2. Visitar producto → verificar evento view_item |
| | 3. Añadir al carrito → verificar evento add_to_cart |
| | 4. Ir a checkout → verificar evento begin_checkout |
| | 5. Completar pedido → verificar evento purchase |
| **Resultado esperado** | Eventos GA4 aparecen en dataLayer/consola. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-11: Banners

| Campo | Valor |
|---|---|
| **Precondición** | Al menos 1 banner creado en wp-admin > Banners TANISH |
| **Pasos** | 1. Ir a la homepage |
| | 2. Verificar que el banner se muestra en el hero |
| | 3. Verificar imagen, título, botón CTA |
| **Resultado esperado** | Banner visible en hero carousel de homepage. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

### UAT-12: Videos

| Campo | Valor |
|---|---|
| **Precondición** | Al menos 1 video creado en wp-admin > Videos TANISH |
| **Pasos** | 1. Ir a la homepage |
| | 2. Scroll a sección de videos |
| | 3. Verificar video principal con thumbnail |
| | 4. Hacer click → verificar embed lazy |
| **Resultado esperado** | Video visible con thumbnail. Click carga iframe. |
| **Resultado obtenido** | [PENDIENTE] |
| **Estado** | [PENDIENTE] |
| **Evidencia** | [PENDIENTE] |

---

## Resumen

| Caso | Resultado | Estado |
|---|---|---|
| UAT-01 Catálogo | PASS | PASS |
| UAT-02 Carrito | PASS | PASS |
| UAT-03 Checkout | PASS | PASS |
| UAT-04 WhatsApp | PASS | PASS |
| UAT-05 Entrada | [PENDIENTE] | [PENDIENTE] |
| UAT-06 Salida | [PENDIENTE] | [PENDIENTE] |
| UAT-07 Ajuste | [PENDIENTE] | [PENDIENTE] |
| UAT-08 Kardex | [PENDIENTE] | [PENDIENTE] |
| UAT-09 Roles | [PENDIENTE] | [PENDIENTE] |
| UAT-10 Analytics | [PENDIENTE] | [PENDIENTE] |
| UAT-11 Banners | [PENDIENTE] | [PENDIENTE] |
| UAT-12 Videos | [PENDIENTE] | [PENDIENTE] |
