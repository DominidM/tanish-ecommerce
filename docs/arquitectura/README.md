# Arquitectura — TANISH

Documentación de arquitectura del proyecto: estado actual y evolución propuesta.

## Documentos

| Documento | Contenido | Uso |
|---|---|---|
| [ARQUITECTURA-ACTUAL.md](./ARQUITECTURA-ACTUAL.md) | Cómo funciona hoy el sistema: monolito modular WordPress + WooCommerce, capas, datos, infraestructura y limitaciones. | Línea base técnica y evidencia del "estado antes". |
| [PROPUESTA-LARAVEL.md](./PROPUESTA-LARAVEL.md) | Evolución a una arquitectura orientada a servicios (SOA) de dos servicios: WordPress + WooCommerce como comercio y Laravel como servicio de integración vía webhooks y REST API. | Propuesta de mejora, plan por fases y sustento académico. |
| [ADR-001-soa-laravel.md](./ADR-001-soa-laravel.md) | Registro de la decisión arquitectónica: contexto, decisión, alternativas descartadas y consecuencias. | Justificación formal de la propuesta. |
| [IMPLEMENTACION-FASES.md](./IMPLEMENTACION-FASES.md) | Estado real de la implementación en la rama `feat/laravel-soa`: qué se construyó, cómo demostrarlo y qué queda pendiente. | Evidencia técnica de avance. |

## Orden de lectura

1. **ARQUITECTURA-ACTUAL.md** — entender qué hay y por qué funciona.
2. **PROPUESTA-LARAVEL.md** — entender qué se propone y cómo se implementa sin romper lo existente.
3. **ADR-001-soa-laravel.md** — justificar la decisión y sus trade-offs.

## Principio de la propuesta

La propuesta **no reemplaza** WordPress ni WooCommerce. Los mantiene como plataforma de comercio (requisito académico) y **suma** un servicio Laravel para inventario, panel administrativo, analítica, notificaciones y formularios. La integración se realiza con **webhooks nativos de WooCommerce** y la **REST API v3**.

```
[ WordPress + WooCommerce ]  ⇄  [ Laravel ]
   comercio, pedidos, stock       panel, kardex, analítica
```

## Alcance de esta documentación

- Describe la arquitectura y su evolución; **no modifica código**.
- Sirve como evidencia para la sustentación del proyecto.
- Cualquier cambio de implementación debe abrir su propio sprint y actualizar `docs/sprints/TRACEABILITY.md`.
