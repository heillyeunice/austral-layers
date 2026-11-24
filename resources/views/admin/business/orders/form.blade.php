@extends('layouts.app', [
    'pageTitle' => 'Create order — Austral Layers',
    'pageDescription' => 'Create and assign a business order.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <a class="staff-auth-back" href="{{ route('admin.business.orders.index') }}">← Orders</a>
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> Sales &amp; operations</p><h1>Create an order</h1><p>Record the customer, project details, price, and next step.</p></div></div>
        @if (session('business_saved'))
            <p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>
        @endif
        @if ($errors->getBag('businessOrder')->any())
            <div class="staff-auth-error" role="alert">@foreach ($errors->getBag('businessOrder')->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form class="staff-editor-form business-order-form" method="post" enctype="multipart/form-data" action="{{ route('admin.business.orders.store') }}" data-idempotent-form data-submitting-label="Creating order...">
                @csrf
                <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
                <div class="staff-editor-grid">
                    <label>Customer name
                        <input name="customer_name" list="business-customer-suggestions" value="{{ old('customer_name', $prefillCustomer?->name) }}" required maxlength="150" autocomplete="off">
                        <datalist id="business-customer-suggestions">
                            @foreach ($customers as $customer)<option value="{{ $customer->name }}">{{ $customer->company ?: 'Customer' }}</option>@endforeach
                        </datalist>
                        <small>Existing names are suggested. New names create a customer profile you can complete later.</small>
                    </label>
                    <label>Where was the order placed?
                        <select name="sales_channel" required>
                            <option value="">Choose a channel</option>
                            @foreach ($salesChannels as $value => $label)
                                <option value="{{ $value }}" @selected(old('sales_channel') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Product or service <input name="item_name" value="{{ old('item_name') }}" required maxlength="180"></label>
                    <label>Order date <input type="date" name="order_date" value="{{ old('order_date', today()->toDateString()) }}" required></label>
                    <label>Deadline <input type="date" name="deadline" value="{{ old('deadline') }}"></label>
                    <label>Total price (PHP / ₱) <input type="number" min="50" max="9999999999.99" step="0.01" name="total_price" value="{{ old('total_price', '50.00') }}" required><small>Minimum order total: PHP (₱) 50.00.</small></label>
                    <label>Order status
                        <select name="status" required>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', 'new_order') === $value)>{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Overall order owner
                        <select name="assigned_staff_id"><option value="">Unassigned</option>@foreach ($staff as $user)<option value="{{ $user->id }}" @selected((string) old('assigned_staff_id') === (string) $user->id)>{{ $user->name }}{{ $user->position ? ' · '.$user->position : '' }}</option>@endforeach</select>
                        <small>Assign individual production stages from the order tracker.</small>
                    </label>
                    <label>Shipping method <input name="shipping_method" value="{{ old('shipping_method') }}" maxlength="100"></label>
                    <label class="staff-editor-wide">Description <textarea name="description" required maxlength="2000">{{ old('description') }}</textarea></label>
                    <label class="staff-editor-wide">Specifications <textarea name="specifications" maxlength="2000">{{ old('specifications') }}</textarea></label>
                    <label class="staff-editor-wide">Reference images or files
                        <input id="new-order-reference-files" type="file" name="reference_files[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple data-file-input>
                        <small data-file-input-status>No files selected.</small>
                        <small>Up to five files, 10 MB each. Files stay private to staff.</small>
                    </label>
                    <label class="staff-editor-wide">Internal notes <textarea name="internal_notes" maxlength="4000">{{ old('internal_notes') }}</textarea></label>
                </div>
                <button class="quote-button staff-auth-submit" type="submit" data-idempotent-submit>Create order <span>↗</span></button>
        </form>
    </section>
@endsection
