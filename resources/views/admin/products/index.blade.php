@extends('layouts.app', [
    'pageTitle' => 'Manage products — Austral Layers',
    'pageDescription' => 'Manage Austral Layers products.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Website content</p>
                <h1>Products</h1>
                <p>Update product details, photos, and store links.</p>
            </div>
            <a class="quote-button staff-content-action" href="{{ route('admin.products.create') }}">Add product <span>＋</span></a>
        </div>

        @if (session('product_saved'))
            <p class="staff-admin-success" role="status">{{ session('product_saved') }}</p>
        @endif

        <div class="staff-content-list">
            @forelse ($products as $product)
                <article class="staff-content-row">
                    <div class="staff-content-thumb">
                        @if (!empty($product->images[0]))
                            <img src="{{ asset($product->images[0]) }}" alt="">
                        @else
                            <span>No photo</span>
                        @endif
                    </div>
                    <div class="staff-content-summary">
                        <p>{{ $product->category }}</p>
                        <h2>{{ $product->name }}</h2>
                        <span>{{ count($product->order_links ?? []) }} order links</span>
                    </div>
                    <a class="staff-content-edit" href="{{ route('admin.products.edit', $product->slug) }}">Edit <span>↗</span></a>
                    <form method="post" action="{{ route('admin.products.destroy', $product->slug) }}" onsubmit="return confirm('Delete this product?')">
                        @csrf
                        @method('DELETE')
                        <button class="staff-content-delete" type="submit">Delete</button>
                    </form>
                </article>
            @empty
                <p class="staff-empty-state">No products yet.</p>
            @endforelse
        </div>

        {{ $products->links() }}
    </section>
@endsection
