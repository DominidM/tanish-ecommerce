export interface Env {
  ORIGIN_URL?: string;
  SITE_NAME?: string;
}

const FALLBACK_TITLE = "TANISH MVP";

function htmlResponse(body: string, status = 200): Response {
  return new Response(body, {
    status,
    headers: {
      "content-type": "text/html; charset=UTF-8",
      "cache-control": "no-store, max-age=0",
    },
  });
}

function fallbackPage(siteName: string, originUrl?: string): Response {
  const originLine = originUrl
    ? `<p class="meta">Origen configurado: <code>${escapeHtml(originUrl)}</code></p>`
    : `<p class="meta">Falta configurar <code>ORIGIN_URL</code> para apuntar a tu WordPress o Tunnel.</p>`;

  return htmlResponse(`
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${escapeHtml(siteName)} - ${FALLBACK_TITLE}</title>
    <style>
      :root {
        color-scheme: dark;
        --bg: #07111f;
        --panel: rgba(12, 21, 37, 0.84);
        --border: rgba(148, 163, 184, 0.18);
        --text: #e5eef9;
        --muted: #9fb0c6;
        --accent: #62d0a2;
        --accent-2: #7dd3fc;
      }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        min-height: 100vh;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: var(--text);
        background:
          radial-gradient(circle at top left, rgba(98, 208, 162, 0.18), transparent 30%),
          radial-gradient(circle at top right, rgba(125, 211, 252, 0.18), transparent 26%),
          linear-gradient(160deg, #05101d 0%, #0b1727 52%, #07111f 100%);
      }
      main {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 32px;
      }
      .card {
        width: min(920px, 100%);
        border: 1px solid var(--border);
        border-radius: 24px;
        background: var(--panel);
        backdrop-filter: blur(18px);
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        padding: clamp(24px, 4vw, 48px);
      }
      .eyebrow {
        display: inline-flex;
        gap: 10px;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid rgba(98, 208, 162, 0.35);
        color: var(--accent);
        font-size: 12px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
      }
      h1 {
        margin: 18px 0 12px;
        font-size: clamp(42px, 7vw, 76px);
        line-height: 0.95;
        letter-spacing: -0.06em;
      }
      p {
        margin: 0;
        color: var(--muted);
        line-height: 1.65;
        font-size: 17px;
        max-width: 60ch;
      }
      .grid {
        margin-top: 28px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
      }
      .tile {
        padding: 18px;
        border-radius: 18px;
        border: 1px solid var(--border);
        background: rgba(255, 255, 255, 0.03);
      }
      .tile strong {
        display: block;
        margin-bottom: 8px;
        color: var(--accent-2);
      }
      .cta {
        margin-top: 28px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
      }
      a.button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid transparent;
      }
      a.primary {
        color: #062016;
        background: linear-gradient(135deg, var(--accent), #a7f3d0);
      }
      a.secondary {
        color: var(--text);
        border-color: var(--border);
        background: rgba(255, 255, 255, 0.02);
      }
      .meta {
        margin-top: 18px;
        font-size: 14px;
      }
      code {
        color: #d1fae5;
        background: rgba(255, 255, 255, 0.08);
        padding: 2px 6px;
        border-radius: 6px;
      }
      @media (max-width: 760px) {
        .grid { grid-template-columns: 1fr; }
        .card { border-radius: 20px; }
      }
    </style>
  </head>
  <body>
    <main>
      <section class="card">
        <span class="eyebrow">Cloudflare Worker MVP</span>
        <h1>${escapeHtml(siteName)}</h1>
        <p>
          Esta capa de entrada está lista para mostrar el storefront del cliente en Cloudflare.
          Si el origen no responde, verás esta landing de respaldo mientras conectas el WordPress.
        </p>
        <div class="grid">
          <div class="tile">
            <strong>Proxy</strong>
            Reenvia el tráfico al WordPress real cuando definas <code>ORIGIN_URL</code>.
          </div>
          <div class="tile">
            <strong>Demo</strong>
            Sirve una experiencia presentable aunque el backend local siga cerrado.
          </div>
          <div class="tile">
            <strong>Cloudflare</strong>
            Listo para desplegar con <code>wrangler login</code> y <code>wrangler deploy</code>.
          </div>
        </div>
        <div class="cta">
          <a class="button primary" href="/">Reintentar origen</a>
          <a class="button secondary" href="/wp-admin">Ir a WordPress</a>
        </div>
        ${originLine}
      </section>
    </main>
  </body>
</html>
  `);
}

function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

async function proxyRequest(request: Request, originUrl: string): Promise<Response> {
  const incomingUrl = new URL(request.url);
  const origin = new URL(originUrl);

  const targetUrl = new URL(incomingUrl.pathname + incomingUrl.search, origin);
  const upstreamRequest = new Request(targetUrl.toString(), request);

  upstreamRequest.headers.set("x-forwarded-host", incomingUrl.host);
  upstreamRequest.headers.set("x-forwarded-proto", incomingUrl.protocol.replace(":", ""));

  return fetch(upstreamRequest);
}

export default {
  async fetch(request, env): Promise<Response> {
    const siteName = env.SITE_NAME || "TANISH";
    const originUrl = env.ORIGIN_URL?.trim();

    if (!originUrl) {
      return fallbackPage(siteName);
    }

    try {
      return await proxyRequest(request, originUrl);
    } catch {
      return fallbackPage(siteName, originUrl);
    }
  },
};