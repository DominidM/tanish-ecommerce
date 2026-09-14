# Evidencias Sprint 01 — B2C Commerce

**Fecha:** 14 Sep 2026
**Ambiente:** localhost:8080 (Docker: tanish-wordpress + tanish-mysql)

## Resumen de pruebas

| Prueba | Resultado | Detalle |
|--------|-----------|---------|
| Add-to-cart en producto individual | PASS | `<button name="add-to-cart" value="21">` presente |
| Carrito (Store API) | PASS | 1 item, Arroz Extra 1 kg, S/ 5.50 |
| Checkout accesible | PASS | HTTP 200 (con items en carrito) |
| Envío (Flat Rate) | PASS | Zone Perú, instance 2, enabled |
| COD disponible | PASS | `is_available() = true`, `get_available_payment_gateways() = [cod]` |
| Pedido de prueba creado | PASS | Order ID: 60, status: processing, total: S/ 5.50 |
| Stock reducido | PASS | 30 → 29 tras pedido |
| WhatsApp producto | PASS | wa.me link presente en página individual |
| WhatsApp dock | PASS | 5 referencias wa.me en homepage |
| Páginas HTTP | PASS | Home, Shop, Product, Cart, MyAccount = 200 |
| Paginación shop | PASS | /shop/page/2/ funciona |
| Coexistencia B2C + WhatsApp | PASS | Add-to-cart y botón WhatsApp coexisten |

## Datos del pedido de prueba

- **Order ID:** 60
- **Estado:** processing
- **Método de pago:** cod (Pago contra entrega)
- **Producto:** Arroz Extra 1 kg (ID: 21, SKU: TAN-ARR-001)
- **Cantidad:** 1
- **Subtotal:** S/ 5.50
- **Envío:** S/ 0.00 (Flat Rate)
- **Total:** S/ 5.50 PEN
- **Stock antes:** 30
- **Stock después:** 29
- **Cliente:** Cliente Prueba TANISH (prueba@tanish-test.com)

## Notas técnicas

### Corrupción detectada y reparada

El option `woocommerce_cod_settings` quedó con datos serializados corruptos
durante la sesión anterior (longitudes incorrectas de strings con caracteres
multibyte UTF-8: "entrega" tenía `s:20` en vez de `s:19`, "Coordinar..." tenía
`s:30` en vez de `s:29`, etc.).

**Causa:** Las cadenas serializadas se construyeron manualmente con SQL en vez
de usar `serialize()` de PHP. El `strlen()` de PHP cuenta bytes, no caracteres,
y "ó" ocupa 2 bytes en UTF-8.

**Reparación:** Se actualizó el option usando `WPDB::update()` pasando un array
PHP correctamente serializado por `serialize()` de PHP.

### Archivos modificados (Sprint 01)

| Archivo | Cambio |
|---------|--------|
| `plugins/tanish-inventory/includes/class-tanish-whatsapp.php:158` | `remove_action` comentado para restaurar add-to-cart |

### Archivos NO modificados

- `themes/tanish-storefront/functions.php` — intacto
- WooCommerce core — intacto
- Ningún otro plugin — intacto
