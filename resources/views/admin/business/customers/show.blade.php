@extends('layouts.app', [
    'pageTitle' => $customer->name.' — Customers — Austral Layers',
    'pageDescription' => 'Customer profile and order history.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <a class="staff-auth-back" href="{{ route('admin.business.customers.index') }}">← Customers</a>
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> Customer profile</p><h1>{{ $customer->name }}</h1><p>{{ $customer->company ?: 'Individual customer' }}</p></div><a class="quote-button staff-content-action" href="{{ route('admin.business.orders.create', ['customer_id' => $customer->id]) }}">Create order <span>＋</span></a></div>
        @if (session('business_saved'))<p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>@endif
        @if ($errors->getBag('businessCustomer')->any())
            <div class="staff-auth-error" role="alert">@foreach ($errors->getBag('businessCustomer')->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <div class="business-dashboard-columns business-form-layout">
            <section class="business-panel">
                <h2>Contact details</h2>
                <form class="staff-editor-form" method="post" action="{{ route('admin.business.customers.update', $customer) }}">
                    @csrf @method('PUT')
                    <label>Name <input name="name" value="{{ old('name', $customer->name) }}" required maxlength="150"></label>
                    <label>Company <input name="company" value="{{ old('company', $customer->company) }}" maxlength="150"></label>
                    <label>Email <input type="email" name="email" value="{{ old('email', $customer->email) }}" maxlength="190"></label>
                    <label>Phone <input name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="50"></label>
                    <label class="staff-editor-wide">Shipping address <textarea name="shipping_address" maxlength="2000">{{ old('shipping_address', $customer->shipping_address) }}</textarea></label>
                    <label>Social profile link <input type="url" name="social_media_link" value="{{ old('social_media_link', $customer->social_media_link) }}" maxlength="2048"></label>
                    <label>Notes <textarea name="notes" maxlength="2000">{{ old('notes', $customer->notes) }}</textarea></label>
                    <button class="quote-button staff-auth-submit" type="submit">Save details <span>↗</span></button>
                </form>
            </section>
            <section class="business-panel">
                <h2>Order history</h2>
                @forelse ($customer->orders as $order)
                    <a class="business-list-row" href="{{ route('admin.business.orders.show', $order) }}">
                        <span><strong>{{ $order->item_name }}</strong><small>{{ $order->order_number }} · {{ $order->order_date->format('M j, Y') }}</small></span>
                        <span class="business-status">{{ \App\Models\BusinessOrder::STATUSES[$order->status] ?? $order->status }}</span>
                    </a>
                @empty
                    <p class="staff-empty-state">No orders for this customer yet.</p>
                @endforelse
            </section>
        </div>
    </section>
@endsection
