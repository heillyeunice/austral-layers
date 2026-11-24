@extends('layouts.app', [
    'pageTitle' => $product['name'] . ' — Austral Layers',
    'pageDescription' => $product['description'],
])

@section('content')
    <section class="product-detail">
        <a class="product-back" href="{{ route('products.index') }}">← All products</a>
        <div class="product-detail-grid">
            <div class="product-detail-images">
                @forelse ($product['images'] as $image)
                    <figure>
                        <img src="{{ asset($image) }}" alt="{{ $product['name'] }} by Austral Layers">
                    </figure>
                @empty
                    <span class="photo-pending">Product photos<br>coming soon</span>
                @endforelse
            </div>
            <div class="product-detail-copy">
                <p class="eyebrow"><span></span> {{ $product['category'] }}</p>
                <h1>{{ $product['name'] }}</h1>
                <p>{{ $product['description'] }}</p>
                <div class="order-options">
                    <h2>Order here</h2>
                    <p>Choose your preferred store to place an order.</p>
                    <div class="order-links">
                        @foreach ($product['order_links'] as $store => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">Order on {{ $store }} <span>↗</span></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
