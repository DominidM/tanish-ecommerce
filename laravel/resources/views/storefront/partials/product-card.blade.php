@php
    $image = $product->image_url;
    $price = (float) $product->price;
    $stock = (float) ($product->stock_quantity ?? 0);
    $soldOut = $product->manage_stock && $stock <= 0;
@endphp

<article class="product-card">
    <a class="product-media" href="{{ route('storefront.product', $product->slug ?: $product->woo_id) }}">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="product-placeholder">{{ mb_substr($product->name, 0, 1) }}</span>
        @endif

        @if ($soldOut)
            <span class="badge badge-out">Agotado</span>
        @elseif ($product->isLowStock())
            <span class="badge badge-low">Últimas unidades</span>
        @endif
    </a>

    <div class="product-body">
        <h3>
            <a href="{{ route('storefront.product', $product->slug ?: $product->woo_id) }}">{{ $product->name }}</a>
        </h3>

        <p class="price">
            {{ config('tanish.currency', 'PEN') }} {{ number_format($price, 2) }}
        </p>

        <div class="product-actions">
            <form method="POST" action="{{ route('quote.add', $product) }}">
                @csrf
                <input type="hidden" name="quantity" value="1">
                <button class="btn btn-sm" type="submit" @disabled($soldOut)>Agregar</button>
            </form>

            <a class="btn btn-ghost btn-sm" href="{{ route('storefront.product', $product->slug ?: $product->woo_id) }}">Ver</a>
        </div>
    </div>
</article>
