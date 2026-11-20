export interface Env {
  ORIGIN_URL?: string;
  SITE_NAME?: string;
}

const DEFAULT_SITE_NAME = "TANISH";

function htmlResponse(body: string, status = 200): Response {
  return new Response(body, {
    status,
    headers: {
      "content-type": "text/html; charset=UTF-8",
      "cache-control": "no-store, max-age=0",
    },
  });
}

function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function pageShell(title: string, body: string): string {
  return `<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${escapeHtml(title)}</title>
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        background: #0b1220;
        color: #e5eef9;
      }
      main { width: min(640px, 100%); }
      h1 { font-size: clamp(26px, 5vw, 38px); margin: 0 0 16px; }
      p { color: #b6c4d6; line-height: 1.6; margin: 0 0 12px; }
      code {
        color: #d1fae5;
        background: rgba(255, 255, 255, 0.08);
        padding: 2px 6px;
        border-radius: 6px;
      }
      ul { color: #b6c4d6; line-height: 1.7; padding-left: 20px; }
      .tag {
        display: inline-block;
        margin-bottom: 16px;
        font-size: 12px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #fca5a5;
      }
    </style>
  </head>
  <body>
    <main>
      ${body}
    </main>
  </body>
</html>`;
}

function configErrorPage(siteName: string, reason: string): Response {
  return htmlResponse(
    pageShell(
      `${siteName} - Origen no configurado`,
      `<span class="tag">503 · Error de configuración</span>
      <h1>Origen no configurado</h1>
      <p>Este Worker no puede atender tráfico porque <code>ORIGIN_URL</code> no está definida o no es una URL http(s) válida.</p>
      <p>Detalle: ${escapeHtml(reason)}</p>
      <p>Configura el origen real del WordPress antes de desplegar:</p>
      <ul>
        <li>Producción: <code>wrangler secret put ORIGIN_URL</code> (o una var de entorno en el dashboard).</li>
        <li>Local: <code>wrangler dev --var ORIGIN_URL:http://localhost:8080</code>.</li>
      </ul>
      <p>No se muestra ninguna landing de respaldo: el sitio está fuera de servicio hasta configurar el origen.</p>`,
    ),
    503,
  );
}

function badGatewayPage(siteName: string, originUrl: string, reason: string): Response {
  return htmlResponse(
    pageShell(
      `${siteName} - Origen no disponible`,
      `<span class="tag">502 · Bad Gateway</span>
      <h1>El origen no responde</h1>
      <p>No se pudo contactar con <code>${escapeHtml(originUrl)}</code>. Revisa que WordPress esté levantado y accesible desde Cloudflare.</p>
      <p>Detalle: ${escapeHtml(reason)}</p>`,
    ),
    502,
  );
}

/**
 * Devuelve la URL del origen solo si es http(s) válida.
 */
function parseOrigin(raw: string | undefined): URL | null {
  if (!raw || raw.trim() === "") {
    return null;
  }

  try {
    const url = new URL(raw.trim());
    if (url.protocol !== "http:" && url.protocol !== "https:") {
      return null;
    }
    return url;
  } catch {
    return null;
  }
}

/**
 * IP del cliente tal como la ve Cloudflare (no la cadena XFF que envía el
 * cliente, que es falsificable).
 */
function clientIp(request: Request): string {
  const cfConnecting = request.headers.get("cf-connecting-ip")?.trim();
  if (cfConnecting) {
    return cfConnecting;
  }

  const xff = request.headers.get("x-forwarded-for");
  if (xff) {
    const first = xff.split(",")[0]?.trim();
    if (first) {
      return first;
    }
  }

  return "unknown";
}

/**
 * Reescribe una cookie para que el navegador la asocie al host de entrada.
 * En localhost/IP se elimina `Domain` (host-only), porque los navegadores
 * rechazan Domain=localhost o Domain=<IP>.
 */
function rewriteSetCookie(cookie: string, hostname: string): string {
  const isLocalHost =
    hostname === "localhost" ||
    hostname.endsWith(".localhost") ||
    /^\[?[0-9a-f:.]+\]?$/i.test(hostname);

  if (isLocalHost) {
    return cookie.replace(/;\s*domain=[^;]*/gi, "");
  }

  if (/;\s*domain=/i.test(cookie)) {
    return cookie.replace(/;\s*domain=[^;]*/gi, `; Domain=${hostname}`);
  }

  return cookie;
}

/**
 * Reescribe `Location` para que los redirects del origen apunten al host
 * público del Worker y no al dominio interno.
 */
function rewriteLocation(location: string, origin: URL, incoming: URL): string {
  try {
    const target = new URL(location, origin);
    target.protocol = incoming.protocol;
    target.host = incoming.host;
    return target.toString();
  } catch {
    return location;
  }
}

async function proxyRequest(request: Request, origin: URL): Promise<Response> {
  const incomingUrl = new URL(request.url);

  const targetUrl = new URL(incomingUrl.pathname + incomingUrl.search, origin);
  const upstreamRequest = new Request(targetUrl.toString(), request);

  upstreamRequest.headers.set("x-forwarded-host", incomingUrl.host);
  upstreamRequest.headers.set("x-forwarded-proto", incomingUrl.protocol.replace(":", ""));
  // Se sobrescribe (no se concatena) para que el origen no confíe en una
  // cadena XFF enviada por el cliente.
  upstreamRequest.headers.set("x-forwarded-for", clientIp(request));
  upstreamRequest.headers.set("x-real-ip", clientIp(request));

  // `manual` permite devolver el 3xx al navegador con Location reescrito en
  // lugar de seguir el redirect internamente.
  const upstream = await fetch(upstreamRequest, { redirect: "manual" });

  const headers = new Headers(upstream.headers);

  const location = headers.get("location");
  if (location) {
    headers.set("location", rewriteLocation(location, origin, incomingUrl));
  }

  const cookies =
    (upstream.headers as Headers & { getSetCookie?: () => string[] }).getSetCookie?.() ?? [];
  if (cookies.length > 0) {
    headers.delete("set-cookie");
    for (const cookie of cookies) {
      headers.append("set-cookie", rewriteSetCookie(cookie, incomingUrl.hostname));
    }
  }

  // 204/304 no pueden llevar body.
  if (upstream.status === 204 || upstream.status === 304) {
    return new Response(null, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers,
    });
  }

  return new Response(upstream.body, {
    status: upstream.status,
    statusText: upstream.statusText,
    headers,
  });
}

export default {
  async fetch(request, env): Promise<Response> {
    const siteName = env.SITE_NAME?.trim() || DEFAULT_SITE_NAME;
    const origin = parseOrigin(env.ORIGIN_URL);

    if (!origin) {
      const reason = env.ORIGIN_URL?.trim()
        ? `Valor recibido: ${env.ORIGIN_URL}`
        : "La variable no está definida.";
      return configErrorPage(siteName, reason);
    }

    try {
      return await proxyRequest(request, origin);
    } catch (error) {
      const reason = error instanceof Error ? error.message : String(error);
      return badGatewayPage(siteName, origin.toString(), reason);
    }
  },
};
