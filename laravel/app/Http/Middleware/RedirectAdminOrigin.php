<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        // Si alguien entra al panel por el origen interno (admin.IP.nip.io)
        // en lugar del dominio público, se le redirige al dominio unificado.
        // Las peticiones del Worker traen X-Forwarded-Host y no se redirigen.
        if (
            $appHost
            && $request->is('admin', 'admin/*')
            && $request->getHost() !== $appHost
            && blank($request->header('X-Forwarded-Host'))
        ) {
            return redirect()->to($appUrl.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
