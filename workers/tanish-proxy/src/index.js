// Worker proxy inverso para TANISH.
// usuario -> *.workers.dev (HTTPS, Cloudflare) -> VPS Contabo (HTTP) -> Caddy -> WordPress
export default {
  async fetch(request, env) {
    const origin = new URL(env.ORIGIN);
    const incoming = new URL(request.url);

    // Reconstruye la URL apuntando al VPS, conservando path, query y método.
    const target = new URL(request.url);
    target.protocol = origin.protocol;
    target.hostname = origin.hostname;
    target.port = origin.port;

    const headers = new Headers(request.headers);
    // Nota: Cloudflare fetch() ignora el Host personalizado (usa el de la URL),
    // así que el enrutamiento por Host lo resuelve Caddy en el origen.
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

      // Limpia headers que no deben viajar de vuelta al cliente.
      const responseHeaders = new Headers(response.headers);
      responseHeaders.delete("alt-svc");

      return new Response(response.body, {
        status: response.status,
        statusText: response.statusText,
        headers: responseHeaders,
      });
    } catch (err) {
      return new Response("Origen no disponible. Revisa el VPS de Contabo.", {
        status: 502,
        headers: { "Content-Type": "text/plain; charset=utf-8" },
      });
    }
  },
};
