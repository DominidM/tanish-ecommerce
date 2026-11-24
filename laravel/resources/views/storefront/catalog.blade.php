@extends('layouts.storefront')

@section('title', 'Tienda — TANISH')
@section('meta_description', 'Catálogo completo de útiles escolares y de oficina TANISH.')

@section('content')
    <section class="page-head">
        <div class="container">
            <span class="eyebrow">Catálogo</span>
            <h1>Tienda</h1>

            <form class="filters" method="GET" action="{{ route('storefront.catalog') }}">
                <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre o SKU…">
                <select name="categoria">
                    <option value="0">Todas las categorías</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item['id'] }}" @selected($category === $item['id'])>{{ $item['name'] }}</option>
                    @endforeach
                </select>
                <button class="btn" type="submit">Filtrar</button>
                @if ($search !== '' || $category > 0)
                    <a class="btn btn-ghost" href="{{ route('storefront.catalog') }}">Limpiar</a>
                @endif
            </form>
        </div>
    </section>

    <section class="container section">
        @if ($products->isEmpty())
            <div class="empty">
                <h3>No encontramos productos</h3>
                <p>Prueba con otra búsqueda o revisa el catálogo completo.</p>
                <a class="btn" href="{{ route('storefront.catalog') }}">Ver todo</a>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            @if ($products->hasPages())
                <nav class="pagination" aria-label="Paginación">
                    @if ($products->onFirstPage())
                        <span class="disabled">Anterior</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}">Anterior</a>
                    @endif

                    <span>Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</span>

                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}">Siguiente</a>
                    @else
                        <span class="disabled">Siguiente</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
@endsection
