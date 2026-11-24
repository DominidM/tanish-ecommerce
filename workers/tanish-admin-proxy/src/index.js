// Worker HTTPS para el panel Laravel de TANISH.
// Navegador -> https://tanish-admin.solvegrades.workers.dev
//           -> http://admin.185.176.254.183.nip.io (Caddy -> laravel:80)
//
// No reemplaza al Worker de la tienda; es un Worker independiente para
// dar TLS al panel sin depender de un dominio propio.

function parseOrigin(raw) {
  if (!raw) return null;

  try {
    const url = new URL(raw);
    return url.protocol === "http:" || url.protocol === "https:" ? url : null;
  } catch {
    return null;
  }
}

function clientIp(request) {
  const connecting = request.headers.get("cf-connecting-ip")?.trim();
  if (connecting) return connecting;

  const forwarded = request.headers.get("x-forwarded-for");
  if (forwarded) return forwarded.split(",")[0].trim();

  return "unknown";
}

function rewriteSetCookie(cookie, hostname) {
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

function rewriteLocation(location, origin, incoming) {
  try {
    const target = new URL(location, origin);
    target.protocol = incoming.protocol;
    target.host = incoming.host;

    return target.toString();
  } catch {
    return location;
  }
}

async function proxyRequest(request, origin) {
  const incomingUrl = new URL(request.url);
  const targetUrl = new URL(incomingUrl.pathname + incomingUrl.search, origin);
  const upstreamRequest = new Request(targetUrl.toString(), request);

  upstreamRequest.headers.set("x-forwarded-host", incomingUrl.host);
  upstreamRequest.headers.set("x-forwarded-proto", incomingUrl.protocol.replace(":", ""));
  upstreamRequest.headers.set("x-forwarded-for", clientIp(request));
  upstreamRequest.headers.set("x-real-ip", clientIp(request));

  const upstream = await fetch(upstreamRequest, { redirect: "manual" });
  const headers = new Headers(upstream.headers);

  const location = headers.get("location");
  if (location) {
    headers.set("location", rewriteLocation(location, origin, incomingUrl));
  }

  const cookies =
    (upstream.headers).getSetCookie?.() ?? [];

  if (cookies.length > 0) {
    headers.delete("set-cookie");
    for (const cookie of cookies) {
      headers.append("set-cookie", rewriteSetCookie(cookie, incomingUrl.hostname));
    }
  }

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
  async fetch(request, env) {
    const origin = parseOrigin(env.ORIGIN);

    if (!origin) {
      return new Response("ORIGIN no configurado para tanish-admin", {
        status: 503,
        headers: { "content-type": "text/plain; charset=utf-8" },
      });
    }

    try {
      return await proxyRequest(request, origin);
    } catch (error) {
      return new Response(`Origen no disponible: ${error.message}`, {
        status: 502,
        headers: { "content-type": "text/plain; charset=utf-8" },
      });
    }
  },
};
