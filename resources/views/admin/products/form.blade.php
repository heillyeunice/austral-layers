@extends('layouts.app', [
    'pageTitle' => ($product ? 'Edit product' : 'Add product') . ' — Austral Layers',
    'pageDescription' => 'Manage a product.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">

        <a class="staff-auth-back" href="{{ route('admin.products.index') }}">← Products</a>
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Products</p>
                <h1>{{ $product ? 'Edit product' : 'Add a product' }}</h1>
            </div>
        </div>

        @if ($errors->getBag('productContent')->any())
            <div class="staff-auth-error" role="alert">
                @foreach ($errors->getBag('productContent')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ $product ? route('admin.products.update', $product->slug) : route('admin.products.store') }}">
            @csrf
            @if ($product)
                @method('PUT')
            @endif

            <div class="staff-editor-grid">
                <label>Product name
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}" required maxlength="180">
                </label>
                <label>URL slug
                    <input type="text" name="slug" value="{{ old('slug', $product?->slug) }}" required maxlength="180" pattern="[A-Za-z0-9_-]+">
                </label>
            </div>

            <label>Category
                <input type="text" name="category" value="{{ old('category', $product?->category) }}" required maxlength="120">
            </label>

            <label>Description
                <textarea name="description" rows="4" maxlength="2000" required>{{ old('description', $product?->description) }}</textarea>
            </label>

            <div class="staff-existing-images">
                <p>Current photos</p>
                <div>
                    @forelse ($product?->images ?? [] as $image)
                        <img src="{{ asset($image) }}" alt="{{ $product->name }}">
                    @empty
                        <span>No photos uploaded.</span>
                    @endforelse
                </div>
            </div>

            <label>Product photos
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/heic,image/heif" multiple>
                <small>Choosing photos replaces the current gallery. Up to 10 images, 20 MB each.</small>
            </label>
            @if ($product && count($product->images ?? []))
                <label class="staff-checkbox">
                    <input type="checkbox" name="remove_images" value="1" @checked(old('remove_images'))>
                    Remove all current photos
                </label>
            @endif

            <div class="staff-editor-grid">
                <label>Facebook order link
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $product?->order_links['Facebook'] ?? '') }}" placeholder="https://">
                </label>
                <label>Lazada order link
                    <input type="url" name="lazada_url" value="{{ old('lazada_url', $product?->order_links['Lazada'] ?? '') }}" placeholder="https://">
                </label>
            </div>
            <label>Shopee order link
                <input type="url" name="shopee_url" value="{{ old('shopee_url', $product?->order_links['Shopee'] ?? '') }}" placeholder="https://">
            </label>

            <div class="staff-editor-actions">
                <button class="quote-button staff-auth-submit" type="submit">{{ $product ? 'Save product' : 'Create product' }} <span>↗</span></button>
                <a class="staff-cancel-link" href="{{ route('admin.products.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
