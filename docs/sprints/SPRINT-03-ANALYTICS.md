# SPRINT 03 — Analítica Web

## Nombre del Sprint
Sprint 03: Analítica Web (GA4 + Eventos)

## Objetivo
Implementar la infraestructura de Google Analytics 4 y los eventos de comercio electrónico requeridos por el documento académico.

## Justificación Académica
- OE4: "Integrar Google Analytics 4 para el seguimiento de eventos de comercio electrónico del canal digital"
- Tabla 2: "N.° de eventos view_item, add_to_cart, begin_checkout y purchase registrados en GA4"
- 3.2.2 Problema 4: "carencia de herramientas de analítica digital"

## Secciones del Documento Relacionadas
- 3.4.2 OE4: GA4
- Tabla 7: Google Analytics 4 como herramienta del proyecto
- Tabla 2: Indicadores de eventos

## Estado Inicial
- Sin implementación GA4 en ningún archivo del proyecto
- Sin Measurement ID
- Sin gtag, dataLayer, ni eventos
- Sin Search Console

## Historias de Usuario

### HU-13: Como administrador, quiero configurar el Measurement ID de GA4 desde wp-admin
**Criterio:** Página de configuración "TANISH → Analítica" con campo Measurement ID.

### HU-14: Como visitante, quiero que se registre mi visita a productos (view_item)
**Criterio:** Al ver un producto, se envía evento view_item con item_id, item_name, price.

### HU-15: Como visitante, quiero que se registre cuando añado al carrito (add_to_cart)
**Criterio:** Al hacer click en add_to_cart, se envía evento con item_id, price, quantity.

### HU-16: Como visitante, quiero que se registre cuando inicio checkout (begin_checkout)
**Criterio:** Al llegar a checkout, se envía evento con items y total.

### HU-17: Como visitante, quiero que se registre cuando completo la compra (purchase)
**Criterio:** Al confirmar pedido, se envía evento con order_id, total, items.

### HU-18: Como visitante, quiero que se registre cuando hago click en WhatsApp (click_whatsapp)
**Criterio:** Al hacer click en botón WhatsApp, se envía evento con product_name.

## Requisitos Funcionales
1. Configuración de Measurement ID en wp-admin
2. gtag.js cargado en frontend si Measurement ID está configurado
3. Evento `view_item` en single product
4. Evento `add_to_cart` al añadir al carrito
5. Evento `begin_checkout` en checkout
6. Evento `purchase` tras pedido confirmado
7. Evento `click_whatsapp` en botón WhatsApp
8. Parámetros: item_id/SKU, item_name, price, currency (PEN), quantity
9. Sin exposición de datos personales

## Requisitos No Funcionales
- Measurement ID configurable, no hardcodeado
- Eventos solo se envían si Measurement ID está configurado
- Sin errores de consola
- Compatibile con WooCommerce existente
- Search Console: sitemap.xml y robots.txt

## Tareas Técnicas

### T-18: Crear configuración de analítica en wp-admin
**Archivo:** Nuevo archivo o extensión de plugin
**Acción:** Crear página "TANISH → Analítica" con campo Measurement ID
**Opción WP:** `register_setting()` con opción `tanish_analytics_measurement_id`
**Capacidad:** `manage_tanish_inventory` o `manage_woocommerce`

### T-19: Implementar gtag.js en frontend
**Archivo:** `themes/tanish-storefront/functions.php` o nuevo archivo de plugin
**Acción:** Enqueue gtag.js si Measurement ID está configurado
**Snippet:**
```php
$measurement_id = get_option('tanish_analytics_measurement_id', '');
if (!empty($measurement_id)) {
    // Google tag (gtag.js)
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($measurement_id) . '"></script>';
    echo '<script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag("js", new Date());gtag("config", "' . esc_attr($measurement_id) . '");</script>';
}
```

### T-20: Implementar evento view_item
**Archivo:** `themes/tanish-storefront/functions.php` o template de producto
**Acción:** En single product, agregar script con dataLayer.push para view_item
**Parámetros:** item_id (SKU), item_name, price, currency

### T-21: Implementar evento add_to_cart
**Acción:** Usar hook `woocommerce_add_to_cart` o JS en add_to_cart button
**Parámetros:** item_id, item_name, price, quantity

### T-22: Implementar evento begin_checkout
**Acción:** En template checkout o hook `woocommerce_before_checkout_form`
**Parámetros:** items, value, currency

### T-23: Implementar evento purchase
**Acción:** En hook `woocommerce_thankyou` o `woocommerce_order_status_completed`
**Parámetros:** transaction_id, value, items, currency

### T-24: Implementar evento click_whatsapp
**Acción:** Agregar JS onclick en botón WhatsApp
**Parámetros:** product_name, product_url

### T-25: Crear sitemap.xml
**Acción:** Usar plugin Yoast/Rank Math o generar manualmente
**Alternativa:** Crear sitemap básico con PHP

### T-26: Crear robots.txt
**Acción:** Crear archivo robots.txt en raíz de WordPress

### T-27: Documentar evidencia
**Acción:** Guardar outputs, payloads, configuración
**Archivos:** `docs/evidencias/sprint-03/`

## Criterios de Aceptación
- [ ] Configuración Measurement ID en wp-admin
- [ ] gtag.js cargado solo si ID configurado
- [ ] Evento view_item funcional
- [ ] Evento add_to_cart funcional
- [ ] Evento begin_checkout funcional
- [ ] Evento purchase funcional
- [ ] Evento click_whatsapp funcional
- [ ] sitemap.xml existe
- [ ] robots.txt existe
- [ ] Sin errores de consola

## Pruebas
1. Configurar Measurement ID falso (G-TEST123)
2. Visitar producto → verificar evento view_item en consola
3. Añadir al carrito → verificar evento add_to_cart
4. Ir a checkout → verificar evento begin_checkout
5. Completar pedido → verificar evento purchase
6. Click WhatsApp → verificar evento click_whatsapp
7. Verificar sitemap.xml
8. Verificar robots.txt
9. Quitar Measurement ID → verificar que no se envían eventos

## Evidencias Requeridas
- Configuración Measurement ID (captura)
- Payloads de eventos (dev tools)
- sitemap.xml content
- robots.txt content

## Riesgos
- R-07: Measurement ID no existe → infraestructura lista, BLOCKED en verificación
- R-08: Eventos no se disparan → verificar hooks y JS
- R-09: Search Console requiere cuenta Google → BLOCKED

## Dependencias
- Sprint 01 completado (para eventos add_to_cart, begin_checkout, purchase)

## Definition of Done
- [ ] Configuración Measurement ID funcional
- [ ] gtag.js cargado condicionalmente
- [ ] 5 eventos implementados
- [ ] sitemap.xml creado
- [ ] robots.txt creado
- [ ] Evidencia documentada

## Estado Final
**STATUS: PLANNED**

## Archivos Modificados (planificados)
- `plugins/tanish-inventory/` (nuevo archivo de analítica o extensión)
- `themes/tanish-storefront/functions.php` (gtag enqueue)
- `themes/tanish-storefront/woocommerce/single-product.php` (evento view_item)
- `robots.txt` (nuevo)
- `sitemap.xml` (nuevo o plugin)
- `docs/evidencias/sprint-03/` (nuevo directorio)

## Resultado Esperado
Infraestructura GA4 lista con 5 eventos implementados. Conexión externa BLOCKED hasta tener Measurement ID real.

## Brechas Restantes
- **BLOCKED:** Measurement ID real no disponible
- **BLOCKED:** Google Search Console requiere cuenta/propiedad
- Eventos funcionales solo con Measurement ID configurado
