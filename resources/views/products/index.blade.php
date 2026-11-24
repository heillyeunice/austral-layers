@extends('layouts.app', [
    'pageTitle' => 'Products — Austral Layers',
    'pageDescription' => 'Shop personalized 3D-printed products from Austral Layers.',
])

@section('content')
    <section class="inner-hero products-hero">
        <p class="eyebrow"><span></span> Made to order</p>
        <h1>Useful things.<br><em>Personal touches.</em></h1>
        <p>Discover our current collection of 3D-printed products. Choose a piece to see more and find where to order.</p>
    </section>

    <section class="products-section">
        <div class="section-heading"><p>Our products</p><p>Three things we’ve made for you.</p></div>
        <div class="catalog-grid products-grid">
            @foreach ($products as $product)
                <article class="catalog-card">
                    <a class="catalog-image" href="{{ route('products.show', $product['slug']) }}" aria-label="View {{ $product['name'] }}">
                        @if ($product['image'])
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }} by Austral Layers">
                        @else
                            <span class="photo-pending">Product photo<br>coming soon</span>
                        @endif
                        <span class="catalog-arrow">↗</span>
                    </a>
                    <div class="catalog-copy">
                        <p class="catalog-label">{{ $product['category'] }}</p>
                        <h2><a href="{{ route('products.show', $product['slug']) }}">{{ $product['name'] }}</a></h2>
                        <p>{{ $product['description'] }}</p>
                        <a class="product-view-link" href="{{ route('products.show', $product['slug']) }}">View product <span>↗</span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
