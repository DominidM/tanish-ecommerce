# Prompt: Enfoque de Negocio y Público Objetivo — TANISH E-Commerce

## 1. Identidad del Negocio

**Empresa:** TANISH S.A.C.  
**Nombre comercial:** TANISH  
**Eslogan:** *Compra fácil, rápida y directa.*  
**Sector:** Retail / Comercio Electrónico (B2C) — orientado a productos de consumo masivo, inicialmente bebidas y artículos de abarrotes.

---

## 2. Enfoque del Negocio (Business Focus)

TANISH S.A.C. opera una **plataforma e-business híbrida** que combina:

- **Una tienda virtual pública** (catálogo online) basada en WordPress + WooCommerce.
- **Un módulo interno de gestión de inventario** (plugin propio `tanish-inventory`) que vincula las ventas online con el stock físico de la empresa en tiempo real.

### Modelo operativo

| Aspecto | Descripción |
|---------|-------------|
| **Modelo de venta** | B2C (Business to Consumer) — venta directa al consumidor final. |
| **Canal principal de cierre** | WhatsApp Business (flujo comercial ligero). |
| **Checkout** | No utiliza checkout tradicional de WooCommerce en la versión inicial. El cliente consulta disponibilidad y coordina la compra directamente con un asesor comercial. |
| **Gestión de stock** | Centralizada en WooCommerce; el plugin `tanish-inventory` registra entradas, salidas, ajustes y mantiene un Kardex lógico auditado. |
| **Logística** | Pedidos coordinados manualmente post-contacto por WhatsApp; la salida de inventario se registra internamente al confirmar la venta. |
| **Facturación** | Emisión externa (sistema contable actual); sin integración automática SUNAT en la versión inicial. |

### Propuesta de valor

1. **Catálogo siempre actualizado:** el cliente ve solo productos con stock real.
2. **Atención directa y humana:** canal WhatsApp para resolver dudas, confirmar disponibilidad y cerrar la compra sin fricciones tecnológicas.
3. **Transparencia de inventario:** la empresa sabe exactamente qué vendió, cuándo y cuánto queda, gracias al control de movimientos (Kardex).
4. **Compra rápida:** sin registros complejos, sin carrito obligatorio; el cliente habla y compra.

---

## 3. Público Objetivo (Target Audience)

### Perfil demográfico

- **Ubicación:** Perú (moneda en Soles — S/; número de WhatsApp en formato internacional `51`).
- **Edad:** 18 a 55 años.
- **Nivel socioeconómico:** C y D (masivo) con acceso a internet móvil.
- **Dispositivo:** principalmente smartphones (Android/iOS).

### Perfil conductual

- Usuarios habituales de **WhatsApp** como canal de comunicación cotidiana.
- Clientes que prefieren **atención personalizada** antes que procesos 100% automatizados.
- Consumidores de **productos de primera necesidad** o consumo frecuente (bebidas, abarrotes) que valoran la rapidez.
- Personas que desconfían o evitan formularios largos de registro y pagos online complejos.

### Necesidades que satisface TANISH

| Necesidad del cliente | Cómo lo resuelve TANISH |
|-----------------------|-------------------------|
| Saber si hay stock antes de pedir | Catálogo con indicador de disponibilidad en tiempo real. |
| Comprar sin complicaciones | Botón "Comprar por WhatsApp" que abre chat con mensaje precargado. |
| Tener confianza en el vendedor | Atención directa por WhatsApp; respuesta humana y coordinación de entrega. |
| Evitar sorpresas de precio | Precios claros en el catálogo, sin costos ocultos. |

---

## 4. Diferenciadores clave

- **Integración operativa:** a diferencia de tiendas genéricas, TANISH conecta el frente de ventas (web) con la operación de bodega (inventario físico).
- **Sin dependencia de pasarelas de pago inmediatas:** el MVP usa WhatsApp para validar stock y cerrar ventas, reduciendo la barrera tecnológica tanto para el cliente como para la empresa.
- **Trazabilidad interna:** cada movimiento de stock queda registrado con tipo de operación (venta, merma, ajuste, compra), usuario responsable y timestamp.

---

## 5. Prompt resumido (para uso en IA, presentaciones o documentación)

> **TANISH S.A.C.** es una empresa peruana de retail B2C que opera una plataforma de e-commerce orientada a la venta directa de productos de consumo masivo. Su modelo combina un catálogo online (WordPress + WooCommerce) con un canal de cierre comercial vía WhatsApp, eliminando la fricción del checkout tradicional en su fase inicial. El sistema incluye un módulo propio de gestión de inventario (`tanish-inventory`) que mantiene sincronizado el stock web con el almacén físico mediante un Kardex lógico auditado. El público objetivo son consumidores finales de 18 a 55 años, principalmente usuarios móviles en Perú, que valoran la atención personalizada, la rapidez y la transparencia de stock. La propuesta de valor se centra en *"compra fácil, rápida y directa"*, ofreciendo un catálogo actualizado, comunicación inmediata por WhatsApp y control operativo interno robusto.

---

*Documento generado para el proyecto TANISH E-Commerce — 2026.*
