# SPRINT 01 — Comercio B2C WooCommerce

## Nombre del Sprint
Sprint 01: Comercio B2C WooCommerce

## Objetivo
Restaurar el flujo de comercio electrónico B2C completo (catálogo → producto → carrito → checkout → pedido) manteniendo WhatsApp como canal alternativo.

## Justificación Académica
OE1: "Configurar el catálogo de productos, categorías, precios, carrito y checkout mediante WooCommerce sobre WordPress." El documento describe un flujo B2C que actualmente está deshabilitado en el código.

## Secciones del Documento Relacionadas
- Abstract: "flujo de compra B2C operativo"
- 3.4.2 OE1: "catálogo, categorías, precios, carrito y checkout"
- 5.6 Arquitectura lógica: diagrama de flujo
- Tabla 2: "Porcentaje de módulos WooCommerce configurados"

## Estado Inicial
- Catálogo funcional (12 productos, 15 categorías, precios, stock)
- WhatsApp funcional como canal de venta
- `class-tanish-whatsapp.php:158` elimina `woocommerce_template_single_add_to_cart`
- `class-tanish-whatsapp.php:236-253` reemplaza add-to-cart en loop por "Ver producto"
- `/cart/` existe pero vacía
- `/checkout/` redirige (HTTP 302)
- No hay creación de pedidos WooCommerce

## Historias de Usuario

### HU-01: Como cliente, quiero añadir productos al carrito desde la página del producto
**Criterio:** Botón "Añadir al carrito" visible en single product junto al botón WhatsApp.

### HU-02: Como cliente, quiero ver mi carrito con los productos seleccionados
**Criterio:** `/cart/` muestra productos, cantidades, subtotal, total, botón actualizar, botón eliminar.

### HU-03: Como cliente, quiero completar el checkout con mis datos
**Criterio:** `/checkout/` muestra formulario de facturación y método de pago.

### HU-04: Como cliente, quiero que se genere un pedido tras confirmar el checkout
**Criterio:** Se crea un pedido WooCommerce con ID, cliente, productos, total, estado.

### HU-05: Como cliente, quiero que el stock se actualice tras un pedido confirmado
**Criterio:** WooCommerce descuenta stock al completar el pedido.

### HU-06: Como cliente, quiero seguir pudiendo comprar por WhatsApp
**Criterio:** El botón "Comprar por WhatsApp" sigue funcionando en productos simples.

## Requisitos Funcionales
1. Botón "Añadir al carrito" visible en single product (junto a WhatsApp)
2. Funcionalidad add_to_cart operativa
3. `/cart/` funcional con cantidades, subtotales, actualización, eliminación
4. `/checkout/` con formulario de facturación funcional
5. Método de pago: "Pago contra entrega" o "Coordinación manual" (sin pasarela bancaria real)
6. Pedido WooCommerce creado tras checkout
7. Stock descontado por WooCommerce al confirmar pedido
8. WhatsApp se mantiene como alternativa

## Requisitos No Funcionales
- No modificar WooCommerce core
- No eliminar funcionalidad WhatsApp existente
- Ambos canales (B2C + WhatsApp) deben coexistir
- Checkout funcional sin pasarela de pago real

## Tareas Técnicas

### T-01: Restaurar add_to_cart en single product
**Archivo:** `plugins/tanish-inventory/includes/class-tanish-whatsapp.php`
**Línea:** 150-159 (`maybe_remove_add_to_cart`)
**Acción:** Eliminar o comentar `remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30)`
**Resultado:** Botón "Añadir al carrito" aparece en single product
**NO tocar:** `render_whatsapp_button()` ni el hook del botón WhatsApp

### T-02: Verificar/addicionar botón WhatsApp en single product
**Archivo:** `themes/tanish-storefront/functions.php`
**Línea:** 279-285 (`tanish_storefront_whatsapp_button_hook`)
**Verificar:** Que el botón WhatsApp se muestra después del add-to-cart (priority 35 > 30)
**Resultado:** Ambos botones visibles: [Añadir al carrito] [Comprar por WhatsApp]

### T-03: Verificar /cart/ funcional
**Endpoint:** `http://localhost:8080/cart/`
**Verificar:** Página muestra formulario de carrito (no vacía)
**Si no funciona:** Revisar que WooCommerce cart page ID esté configurado (`woocommerce_cart_page_id=9`)

### T-04: Configurar método de pago
**Acción:** Configurar WooCommerce > Ajustes > Pagos
**Opción:** "Pago contra entrega" (Cash on Delivery) si está disponible, o "Pago manual" (manual payment)
**Resultado:** Checkout acepta un método de pago y permite generar pedido

### T-05: Verificar /checkout/ funcional
**Endpoint:** `http://localhost:8080/checkout/`
**Verificar:** Formulario de facturación visible, método de pago seleccionable
**Resultado:** Checkout funcional sin redirección

### T-06: Test de pedido completo
**Acción:** Añadir producto al carrito → ir a carrito → ir a checkout → completar → verificar pedido
**Verificar:** Order ID creado, cliente registrado, productos listados, total correcto
**Resultado:** Pedido WooCommerce visible en wp-admin > Pedidos

### T-07: Verificar stock tras pedido
**Acción:** Después de crear pedido, verificar stock del producto
**Verificar:** Stock reducido en WooCommerce
**Resultado:** WooCommerce sigue siendo fuente única de stock

### T-08: Verificar WhatsApp sigue funcionando
**Acción:** Visitar single product de un producto simple
**Verificar:** Botón "Comprar por WhatsApp" visible con link wa.me funcional
**Resultado:** Ambos canales operativos

### T-09: Documentar evidencia
**Acción:** Guardar outputs de cada verificación
**Archivos:** `docs/evidencias/sprint-01/`

## Criterios de Aceptación
- [x] add_to_cart funciona en single product
- [x] carrito funciona (/cart/ muestra contenido)
- [x] checkout funciona (/checkout/ muestra formulario)
- [x] pedido se crea en WooCommerce (Order ID: 60)
- [x] stock se comporta correctamente (30 → 29)
- [x] WhatsApp sigue funcionando
- [x] pruebas documentadas
- [x] evidencia guardada

## Pruebas
1. Añadir producto al carrito desde single product
2. Verificar carrito con 1 producto
3. Añadir segundo producto, verificar cantidades
4. Actualizar cantidad en carrito
5. Eliminar producto del carrito
6. Ir a checkout, completar formulario
7. Seleccionar método de pago
8. Confirmar pedido
9. Verificar order ID en wp-admin
10. Verificar stock del producto
11. Verificar botón WhatsApp en producto
12. Verificar que ambos canales coexisten

## Evidencias Requeridas
- Screenshot del single product con ambos botones
- Screenshot del carrito con producto
- Screenshot del checkout funcional
- Order ID del pedido de prueba
- Stock antes/después del pedido
- Output de verificación WhatsApp

## Riesgos
- R-01: Método de pago no configurado → usar "Pago manual"
- R-02: Checkout redirige aún → revisar configuración WC pages
- R-03: WhatsApp se rompe al restaurar add_to_cart → verificar hooks

## Dependencias
- Sprint 00 completado (baseline)

## Definition of Done
- [x] add_to_cart restaurado y funcionando
- [x] carrito funcional verificado
- [x] checkout funcional verificado
- [x] pedido WooCommerce creado (Order ID: 60)
- [x] stock verificado (30 → 29)
- [x] WhatsApp verificado
- [x] evidencia documentada en `docs/evidencias/sprint-01/`

## Estado Final
**STATUS: DONE** (14 Sep 2026)

### Resultado Real
Flujo B2C completo operativo: catálogo → producto → carrito → checkout → pedido (Order ID: 60).
WhatsApp coexistente como canal alternativo.

### Cambio de código
`plugins/tanish-inventory/includes/class-tanish-whatsapp.php:158` — `remove_action` comentado
con anotación `// Sprint 01: RESTORED`.

### Datos del pedido de prueba
- Order ID: 60
- Estado: processing
- Método de pago: cod (Pago contra entrega)
- Producto: Arroz Extra 1 kg (ID: 21, SKU: TAN-ARR-001)
- Cantidad: 1
- Total: S/ 5.50 PEN
- Stock antes: 30 → después: 29

### Incidencia técnica resuelta
`woocommerce_cod_settings` quedó corrupta (serialización manual con longitudes
incorrectas para strings UTF-8). Se reparó usando `WPDB::update()` con array
PHP correctamente serializado via `serialize()`.

## Archivos Modificados (planificados)
- `plugins/tanish-inventory/includes/class-tanish-whatsapp.php` (modificar `maybe_remove_add_to_cart`)
- `docs/evidencias/sprint-01/` (nuevo directorio)

## Resultado Esperado
Flujo B2C funcional: catálogo → producto → carrito → checkout → pedido, con WhatsApp como alternativa.

## Brechas Restantes
- Sin pasarela de pago real (aceptable para MVP académico)
- Pedidos creados manualmente sin integración con inventario TANISH
