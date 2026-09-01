# TANISH MVP Worker

Worker de entrada para presentar el storefront de TANISH en Cloudflare.

## Qué hace

- Reenvia el tráfico a un origen definido por `ORIGIN_URL`.
- Si el origen no responde, muestra una landing de respaldo lista para demo.
- Se despliega con Wrangler y puede apuntar a un WordPress público o a un Cloudflare Tunnel.

## Primeros pasos

1. Entrar a esta carpeta.
2. Instalar dependencias.
3. Iniciar sesion en Cloudflare con Wrangler.
4. Definir `ORIGIN_URL` y desplegar.

```bash
npm install
npm run login
npm run deploy
```

## Variables

- `ORIGIN_URL`: URL publica del WordPress o del Tunnel.
- `SITE_NAME`: nombre mostrado en la landing de respaldo.

## Ejemplos de origen

- WordPress expuesto en un dominio publico.
- Cloudflare Tunnel apuntando a `http://localhost:8080`.

## Nota

Este Worker no guarda secretos. Si luego necesitas una clave o token, usa `wrangler secret put`.