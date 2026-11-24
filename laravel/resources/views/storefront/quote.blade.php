@extends('layouts.storefront')

@section('title', 'Tu cotización — TANISH')
@section('meta_description', 'Revisa tu cotización TANISH y envíala por WhatsApp.')

@section('content')
    <section class="container section">
        <span class="eyebrow">Cotización</span>
        <h1>Tu cotización</h1>

        @if ($items->isEmpty())
            <div class="empty">
                <h3>Tu cotización está vacía</h3>
                <p>Agrega productos del catálogo para armar tu lista.</p>
                <a class="btn" href="{{ route('storefront.catalog') }}">Ir al catálogo</a>
            </div>
        @else
            <div class="quote-layout">
                <div class="quote-list">
                    @foreach ($items as $item)
                        <article class="quote-item">
                            <div class="quote-thumb">
                                @if ($item['product']->image_url)
                                    <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}">
                                @else
                                    <span class="product-placeholder">{{ mb_substr($item['product']->name, 0, 1) }}</span>
                                @endif
                            </div>

                            <div class="quote-info">
                                <h3>
                                    <a href="{{ route('storefront.product', $item['product']->slug ?: $item['product']->woo_id) }}">
                                        {{ $item['product']->name }}
                                    </a>
                                </h3>
                                <p class="muted">
                                    {{ $item['quantity'] }} ×
                                    {{ config('tanish.currency', 'PEN') }} {{ number_format((float) $item['product']->price, 2) }}
                                </p>
                            </div>

                            <div class="quote-subtotal">
                                {{ config('tanish.currency', 'PEN') }} {{ number_format($item['subtotal'], 2) }}
                            </div>

                            <form method="POST" action="{{ route('quote.remove', $item['product']) }}">
                                @csrf
                                <button class="link-danger" type="submit">Quitar</button>
                            </form>
                        </article>
                    @endforeach
                </div>

                <aside class="quote-summary">
                    <h2>Resumen</h2>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>{{ config('tanish.currency', 'PEN') }} {{ number_format($total, 2) }}</strong>
                    </div>

                    <p class="muted">
                        El pago se coordina por WhatsApp o en la tienda oficial. Te confirmamos stock y entrega.
                    </p>

                    <a class="btn btn-block" href="{{ route('quote.whatsapp') }}">Enviar por WhatsApp</a>
                    <a class="btn btn-ghost btn-block" href="{{ route('storefront.catalog') }}">Seguir comprando</a>

                    <form method="POST" action="{{ route('quote.clear') }}">
                        @csrf
                        <button class="link-danger" type="submit">Vaciar cotización</button>
                    </form>
                </aside>
            </div>
        @endif
    </section>
@endsection
