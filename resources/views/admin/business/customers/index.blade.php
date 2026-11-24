@extends('layouts.app', [
    'pageTitle' => 'Customers — Austral Layers',
    'pageDescription' => 'Manage business customers and customer history.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div><p class="eyebrow"><span></span> Sales &amp; operations</p><h1>Customers</h1><p>Keep customer contact details and order history together.</p></div>
        </div>
        @if (session('business_saved'))<p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>@endif
        @if ($errors->getBag('businessCustomer')->any())
            <div class="staff-auth-error" role="alert">@foreach ($errors->getBag('businessCustomer')->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        <div class="business-dashboard-columns business-form-layout">
            <section class="business-panel">
                <h2>Add customer</h2>
                <form class="staff-editor-form" method="post" action="{{ route('admin.business.customers.store') }}">
                    @csrf
                    <label>Name <input name="name" value="{{ old('name') }}" required maxlength="150"></label>
                    <label>Company <input name="company" value="{{ old('company') }}" maxlength="150"></label>
                    <label>Email <input type="email" name="email" value="{{ old('email') }}" maxlength="190"></label>
                    <label>Phone <input name="phone" value="{{ old('phone') }}" maxlength="50"></label>
                    <label class="staff-editor-wide">Shipping address <textarea name="shipping_address" maxlength="2000">{{ old('shipping_address') }}</textarea></label>
                    <label>Social profile link <input type="url" name="social_media_link" value="{{ old('social_media_link') }}" maxlength="2048"></label>
                    <label>Notes <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                    <button class="quote-button staff-auth-submit" type="submit">Add customer <span>＋</span></button>
                </form>
            </section>
            <section class="business-panel">
                <h2>Customer directory</h2>
                <div class="business-table-wrap">
                    <table class="business-table">
                        <thead><tr><th>Customer</th><th>Contact</th><th>Orders</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td><strong>{{ $customer->name }}</strong><small>{{ $customer->company ?: 'Individual customer' }}</small>@if ($customer->shipping_address)<small>{{ $customer->shipping_address }}</small>@endif</td>
                                    <td>{{ $customer->email ?: $customer->phone ?: '—' }}</td>
                                    <td>{{ $customer->orders_count }}</td>
                                    <td><a class="staff-content-edit" href="{{ route('admin.business.customers.show', $customer) }}">View <span>↗</span></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No customers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $customers->links() }}
            </section>
        </div>
    </section>
@endsection
