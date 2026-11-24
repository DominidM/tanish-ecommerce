// Worker de entrada para TANISH.
//
// Un solo dominio público:
//   https://tanish-proxy.solvegrades.workers.dev
//
//   /admin, /livewire*, /css*, /js*, /fonts*, /storage*, /api*, /up
//       -> Laravel (panel + API + assets)
//   todo lo demás (/ , /wp-admin, /wp-json, /shop, ...)
//       -> WordPress (tienda y WooCommerce)
//
// El Worker de WordPress se mantiene igual; solo se agrega el enrutamiento
// por ruta hacia el origen de Laravel.

const LARAVEL_PREFIXES = ["/admin", "/api", "/livewire", "/css", "/js", "/fonts", "/storage"];

function isLaravelPath(pathname) {
  if (pathname === "/up") {
    return true;
  }

  return LARAVEL_PREFIXES.some((prefix) => {
    if (pathname === prefix || pathname.startsWith(`${prefix}/`)) {
      return true;
    }

    // Livewire usa un prefijo con hash: /livewire-46e63dee/update
    return prefix === "/livewire" && pathname.startsWith("/livewire-");
  });
}

function parseOrigin(raw) {
  if (!raw) {
    return null;
  }

  try {
    const url = new URL(raw);
    return url.protocol === "http:" || url.protocol === "https:" ? url : null;
  } catch {
    return null;
  }
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

    // Solo se reescriben redirecciones internas (mismo origen o mismo host
    // público). Los redirects a pasarelas externas se dejan intactos.
    if (target.hostname !== origin.hostname && target.hostname !== incoming.hostname) {
      return location;
    }

    target.protocol = incoming.protocol;
    target.host = incoming.host;
    return target.toString();
  } catch {
    return location;
  }
}

export default {
  async fetch(request, env) {
    const incoming = new URL(request.url);
    const useLaravel = isLaravelPath(incoming.pathname);
    const origin = parseOrigin(useLaravel ? env.LARAVEL_ORIGIN : env.ORIGIN);

    if (!origin) {
      return new Response(
        useLaravel
          ? "LARAVEL_ORIGIN no está configurado."
          : "ORIGIN no está configurado.",
        {
          status: 503,
          headers: { "Content-Type": "text/plain; charset=utf-8" },
        }
      );
    }

    const target = new URL(request.url);
    target.protocol = origin.protocol;
    target.hostname = origin.hostname;
    target.port = origin.port;

    const headers = new Headers(request.headers);
    headers.set("X-Forwarded-Host", incoming.hostname);
    headers.set("X-Forwarded-Proto", "https");
    headers.set("X-Forwarded-Port", "443");

    const originRequest = new Request(target.toString(), {
      method: request.method,
      headers,
      body: request.body,
      redirect: "manual",
    });

    try {
      const response = await fetch(originRequest);
      const responseHeaders = new Headers(response.headers);
      responseHeaders.delete("alt-svc");

      // Los redirects internos (WordPress y Laravel) deben quedar en el
      // dominio público HTTPS. Los externos (pasarelas) no se tocan.
      const location = responseHeaders.get("location");
      if (location) {
        responseHeaders.set("location", rewriteLocation(location, origin, incoming));
      }

      // Solo para Laravel: las cookies deben quedar en el dominio público.
      if (useLaravel) {
        const cookies = response.headers.getSetCookie?.() ?? [];
        if (cookies.length > 0) {
          responseHeaders.delete("set-cookie");
          for (const cookie of cookies) {
            responseHeaders.append("set-cookie", rewriteSetCookie(cookie, incoming.hostname));
          }
        }
      }

      if (response.status === 204 || response.status === 304) {
        return new Response(null, {
          status: response.status,
          statusText: response.statusText,
          headers: responseHeaders,
        });
      }

      return new Response(response.body, {
        status: response.status,
        statusText: response.statusText,
        headers: responseHeaders,
      });
    } catch {
      return new Response("Origen no disponible. Revisa el VPS de Contabo.", {
        status: 502,
        headers: { "Content-Type": "text/plain; charset=utf-8" },
      });
    }
  },
};
