@extends('layouts.storefront')

@section('title', 'TANISH — Útiles escolares y de oficina')
@section('meta_description', 'Catálogo TANISH de útiles escolares y de oficina. Cotiza por WhatsApp y recibe atención personalizada.')

@section('content')
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <span class="eyebrow">Tienda oficial</span>
                <h1>Todo para tu oficina y tu salón de clases</h1>
                <p>Explora el catálogo, arma tu cotización y recíbela por WhatsApp. Atención directa, sin vueltas.</p>
                <div class="hero-actions">
                    <a class="btn" href="{{ route('storefront.catalog') }}">Ver catálogo</a>
                    <a class="btn btn-ghost" href="https://wa.me/{{ config('tanish.whatsapp_number') }}" target="_blank" rel="noopener">Escribir por WhatsApp</a>
                </div>
            </div>
            <div class="hero-panel" aria-hidden="true">
                <div class="hero-stat">
                    <strong>{{ $products->count() }}</strong>
                    <span>productos destacados</span>
                </div>
                <div class="hero-stat">
                    <strong>{{ count($categories) }}</strong>
                    <span>categorías</span>
                </div>
                <div class="hero-stat">
                    <strong>24 h</strong>
                    <span>respuesta promedio</span>
                </div>
            </div>
        </div>
    </section>

    @if (count($categories) > 0)
        <section class="container section">
            <div class="section-head">
                <h2>Categorías</h2>
                <a href="{{ route('storefront.catalog') }}">Ver todo</a>
            </div>
            <div class="chips">
                @foreach ($categories as $category)
                    <a class="chip" href="{{ route('storefront.catalog', ['categoria' => $category['id']]) }}">
                        {{ $category['name'] }}
                        <span>{{ $category['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="container section">
        <div class="section-head">
            <h2>Productos destacados</h2>
            <a href="{{ route('storefront.catalog') }}">Ver catálogo completo</a>
        </div>

        @if ($products->isEmpty())
            <div class="empty">
                <h3>Aún no hay productos sincronizados</h3>
                <p>Ejecuta <code>php artisan woo:sync-products</code> o carga el catálogo de demostración.</p>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>

    <section class="container section">
        <div class="benefits">
            <div class="benefit">
                <h3>Catálogo actualizado</h3>
                <p>Precios y stock sincronizados desde la tienda oficial.</p>
            </div>
            <div class="benefit">
                <h3>Cotiza en segundos</h3>
                <p>Agrega productos y envía tu lista directamente por WhatsApp.</p>
            </div>
            <div class="benefit">
                <h3>Atención personalizada</h3>
                <p>Te ayudamos a elegir y coordinamos la entrega.</p>
            </div>
        </div>
    </section>
@endsection
