@extends('layouts.storefront')

@php
    $image = $product->image_url;
    $price = (float) $product->price;
    $stock = (float) ($product->stock_quantity ?? 0);
    $soldOut = $product->manage_stock && $stock <= 0;
    $description = trim(strip_tags($product->payload['description'] ?? ''));
@endphp

@section('title', $product->name.' — TANISH')
@section('meta_description', \Illuminate\Support\Str::limit($description ?: 'Compra '.$product->name.' en TANISH. Cotiza por WhatsApp.', 150))

@section('content')
    <section class="container section">
        <nav class="breadcrumb" aria-label="Ruta">
            <a href="{{ route('storefront.home') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('storefront.catalog') }}">Tienda</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="product-detail">
            <div class="product-detail-media">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $product->name }}">
                @else
                    <span class="product-placeholder product-placeholder-lg">{{ mb_substr($product->name, 0, 1) }}</span>
                @endif
            </div>

            <div class="product-detail-info">
                <span class="eyebrow">{{ $product->sku ?: 'TANISH' }}</span>
                <h1>{{ $product->name }}</h1>

                <p class="price price-lg">
                    {{ config('tanish.currency', 'PEN') }} {{ number_format($price, 2) }}
                </p>

                <p class="stock {{ $soldOut ? 'stock-out' : ($product->isLowStock() ? 'stock-low' : 'stock-in') }}">
                    @if ($soldOut)
                        Agotado
                    @elseif (! $product->manage_stock)
                        Disponible
                    @elseif ($product->isLowStock())
                        Últimas unidades ({{ rtrim(rtrim(number_format($stock, 2), '0'), '.') }})
                    @else
                        En stock
                    @endif
                </p>

                @if ($description !== '')
                    <p class="muted">{{ $description }}</p>
                @endif

                <form class="add-form" method="POST" action="{{ route('quote.add', $product) }}">
                    @csrf
                    <label for="quantity">Cantidad</label>
                    <input id="quantity" type="number" name="quantity" value="1" min="1"
                        @if ($product->manage_stock) max="{{ max(1, (int) $stock) }}" @endif>
                    <button class="btn" type="submit" @disabled($soldOut)>Agregar a cotización</button>
                </form>

                <a class="btn btn-ghost btn-block" target="_blank" rel="noopener"
                    href="https://wa.me/{{ config('tanish.whatsapp_number') }}?text={{ rawurlencode('Hola TANISH, me interesa '.$product->name) }}">
                    Consultar por WhatsApp
                </a>

                <a class="link-muted" href="{{ config('tanish.store_url') }}/?p={{ $product->woo_id }}" target="_blank" rel="noopener">
                    Ver ficha en la tienda oficial
                </a>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <div class="section-head section-head-spaced">
                <h2>También te puede interesar</h2>
                <a href="{{ route('storefront.catalog') }}">Ver todo</a>
            </div>

            <div class="product-grid">
                @foreach ($related as $relatedProduct)
                    @include('storefront.partials.product-card', ['product' => $relatedProduct])
                @endforeach
            </div>
        @endif
    </section>
@endsection
