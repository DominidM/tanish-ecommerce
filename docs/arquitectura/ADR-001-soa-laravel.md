# ADR-001 — Adoptar una SOA de dos servicios con Laravel

| Campo | Valor |
|---|---|
| Estado | Propuesto (pendiente de validación académica) |
| Fecha | 2026-11-23 |
| Rama | `docs/arquitectura-laravel` |
| Decisores | Equipo TANISH |
| Documentos relacionados | [ARQUITECTURA-ACTUAL.md](./ARQUITECTURA-ACTUAL.md), [PROPUESTA-LARAVEL.md](./PROPUESTA-LARAVEL.md) |

## Contexto

El sistema actual es un monolito modular sobre WordPress + WooCommerce (ver `ARQUITECTURA-ACTUAL.md`). El requisito académico exige mantener WordPress con WooCommerce como plataforma de comercio. Al mismo tiempo, se necesita un panel administrativo moderno, un kardex consolidado, analítica, notificaciones y reportes que hoy viven parcialmente en el admin de WordPress y en código propio.

Migrar todo a Laravel incumpliría el requisito académico y obligaría a reimplementar carrito, checkout, pedidos, stock y pagos. Mantener todo en WordPress perpetúa la deuda de personalización del admin y limita la evolución del panel.

## Decisión

1. **Conservar WordPress + WooCommerce** como servicio de comercio y sistema de registro de catálogo, pedidos, stock y pagos.
2. **Incorporar Laravel + Filament** como segundo servicio, responsable del panel administrativo, kardex, analítica, notificaciones y formularios.
3. **Integrar por contrato explícito:** webhooks nativos de WooCommerce (eventos) y REST API v3 (consultas y escritura acotada de stock).
4. **Separar la propiedad de datos:** esquemas MySQL distintos y usuarios con permisos restringidos; sin acceso SQL cruzado.
5. **Desplegar con un overlay de Compose** y rollback independiente: si Laravel se detiene, la tienda sigue operando.

## Alternativas consideradas

| Alternativa | Motivo de descarte |
|---|---|
| Reemplazar WordPress por Laravel | Incumple el requisito académico y obliga a reimplementar el comercio completo. |
| WordPress headless con Laravel como front público | Válido como fase futura, pero no resuelve el panel ni el inventario a corto plazo y amplía el alcance. |
| Panel administrativo solo en WordPress | No aprovecha Laravel y mantiene la deuda de branding y personalización del admin. |
| Microservicios estrictos | Complejidad operativa innecesaria para dos dominios y un equipo pequeño; implicaría descomponer WooCommerce. |
| Acceso directo a la base de datos de WordPress (SQL/ORM cruzado) | Acoplamiento al esquema interno, riesgo de corrupción y violación de los límites de servicio. |

## Consecuencias

### Positivas

- Se cumple el requisito académico sin reescribir los sprints 00 a 06.
- Panel administrativo moderno y mantenible con Filament.
- Integración explícita, auditable e idempotente basada en eventos.
- Rollback sencillo: detener Laravel y retirar la ruta de Caddy.
- Camino despejado para separar infraestructura en el futuro sin cambiar el contrato.

### Negativas y riesgos

- Dos despliegues y dos bases de datos que operar y monitorear.
- Consistencia eventual entre WooCommerce y el kardex; requiere reconciliación.
- Webhooks pueden perderse, duplicarse o llegar desordenados.
- Mayor superficie de seguridad (nuevo panel, nuevas credenciales).
- Costo inicial de infraestructura (Redis, workers, scheduler).

## Criterios de aceptación

- [ ] WordPress y WooCommerce siguen operando sin Laravel.
- [ ] Laravel no accede por SQL a las tablas de WordPress.
- [ ] Los webhooks se verifican con HMAC y son idempotentes.
- [ ] La reconciliación corrige eventos perdidos.
- [ ] El rollback está documentado y probado.
- [ ] `TRACEABILITY.md` refleja el nuevo estado al ejecutar cada fase.

## Referencias

- `docs/arquitectura/ARQUITECTURA-ACTUAL.md`
- `docs/arquitectura/PROPUESTA-LARAVEL.md`
- `docs/sprints/TRACEABILITY.md`
- `docs/sprints/SPRINT-00-BASELINE.md`
