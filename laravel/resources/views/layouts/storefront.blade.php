<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TANISH — Útiles escolares y de oficina')</title>
    <meta name="description" content="@yield('meta_description', 'TANISH: útiles escolares y de oficina con atención personalizada y compra por WhatsApp.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="TANISH">
    <meta property="og:title" content="@yield('title', 'TANISH')">
    <meta property="og:description" content="@yield('meta_description', 'Útiles escolares y de oficina.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="stylesheet" href="{{ asset('css/tanish-storefront.css') }}">
</head>
<body>
    @php($quoteCount = array_sum(session('quote', [])))

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('storefront.home') }}" class="brand">TANISH<span>.</span></a>
            <nav class="site-nav" aria-label="Navegación principal">
                <a href="{{ route('storefront.home') }}">Inicio</a>
                <a href="{{ route('storefront.catalog') }}">Tienda</a>
                <a href="https://wa.me/{{ config('tanish.whatsapp_number') }}" target="_blank" rel="noopener">WhatsApp</a>
            </nav>
            <a href="{{ route('quote.show') }}" class="quote-link">
                Cotización
                @if ($quoteCount > 0)
                    <span class="badge">{{ $quoteCount }}</span>
                @endif
            </a>
        </div>
    </header>

    @if (session('status'))
        <div class="container">
            <div class="flash">{{ session('status') }}</div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <strong>TANISH</strong>
                <p>Útiles escolares y de oficina. Atención personalizada.</p>
            </div>
            <div class="footer-links">
                <a href="{{ route('storefront.catalog') }}">Tienda</a>
                <a href="{{ route('quote.show') }}">Cotización</a>
                <a href="https://wa.me/{{ config('tanish.whatsapp_number') }}" target="_blank" rel="noopener">WhatsApp</a>
                <a href="{{ url('/admin') }}">Panel</a>
            </div>
        </div>
        <div class="container footer-legal">
            <span>© {{ date('Y') }} TANISH. Todos los derechos reservados.</span>
            <span>Precios en {{ config('tanish.currency', 'PEN') }}.</span>
        </div>
    </footer>

    <a class="whatsapp-fab" href="https://wa.me/{{ config('tanish.whatsapp_number') }}" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">
        WhatsApp
    </a>
</body>
</html>
