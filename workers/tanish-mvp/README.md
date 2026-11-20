# TANISH MVP Worker

Worker de entrada (proxy) para el WordPress de TANISH en Cloudflare.

## Qué hace

- Reenvía el tráfico al WordPress definido por `ORIGIN_URL`.
- Reescribe las cabeceras `Location` y `Set-Cookie` del origen para que
  apunten al host público del Worker (evita saltos al dominio interno).
- Añade `X-Forwarded-For` / `X-Real-IP` con la IP del cliente.
- Si el `fetch` al origen falla, responde **502** con el motivo.
- Si `ORIGIN_URL` falta o no es una URL http(s) válida, responde **503** con
  instrucciones de configuración. No hay landing de respaldo.

## `ORIGIN_URL` es obligatoria

En `wrangler.jsonc` la variable está vacía (`""`) a propósito, tanto en dev como
en `staging` y `production`. Un placeholder como `https://tanish.example.com`
hacía que el Worker pareciera funcionar mientras todo caía en una página falsa.

Configúrala con la URL real del WordPress:

```bash
# Opción recomendada: secreto (no queda en el repo)
npx wrangler secret put ORIGIN_URL --env production

# O como variable de entorno del dashboard de Cloudflare
# ORIGIN_URL = https://tanish.example.com
```

Para desarrollo local:

```bash
npx wrangler dev --port 8789 --local --var ORIGIN_URL:http://localhost:8080
```

Sin esa variable, `npx wrangler dev` responde 503:

```bash
curl -i http://localhost:8789/
# HTTP/1.1 503 Service Unavailable
# ... "Origen no configurado"
```

## Primeros pasos

```bash
npm ci
npx wrangler login
npx wrangler deploy --dry-run   # valida el bundle sin publicar
npx wrangler deploy
```

## Variables

- `ORIGIN_URL` (**obligatoria**): URL pública del WordPress/Tunnel.
- `SITE_NAME`: nombre usado en las páginas de error (503/502).

## Ejemplos de origen

- WordPress expuesto en un dominio público.
- Cloudflare Tunnel apuntando a `http://localhost:8080`.

## Notas

- No se usa `nodejs_compat`: el Worker solo usa APIs Web (fetch, Headers,
  Response, URL), así que se retiró del `wrangler.jsonc` para reducir superficie
  de compatibilidad. Si en el futuro se importa un módulo de Node, vuelve a
  añadirlo.
- Este Worker no guarda secretos en el repo. Usa `wrangler secret put` para
  `ORIGIN_URL` y cualquier credencial futura.
