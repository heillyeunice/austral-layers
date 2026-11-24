@extends('layouts.app', [
    'pageTitle' => 'Business management — Austral Layers',
    'pageDescription' => 'Business operations dashboard for Austral Layers.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-dashboard-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Business overview · {{ now()->format('F Y') }}</p>
                <h1>Business dashboard</h1>
                <p>Sales, cash flow, orders, and deadlines at a glance.</p>
            </div>
            <a class="quote-button staff-content-action" href="{{ route('admin.business.orders.create') }}">Create order <span>＋</span></a>
        </div>

        @if (session('business_saved'))
            <p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>
        @endif

        <div class="business-metric-grid">
            <article class="business-metric-card"><span>Completed sales this month</span><strong>₱{{ number_format($monthGrossSales, 2) }}</strong><small>Based on completed orders</small></article>
            <article class="business-metric-card"><span>Payments received this month</span><strong>₱{{ number_format($monthPayments, 2) }}</strong><small>Recorded customer payments</small></article>
            <article class="business-metric-card"><span>Expenses this month</span><strong>₱{{ number_format($monthExpenses, 2) }}</strong><small>Recorded business expenses</small></article>
            <article class="business-metric-card"><span>Net cash flow this month</span><strong>₱{{ number_format($netCashFlow, 2) }}</strong><small>Payments received less expenses</small></article>
            <article class="business-metric-card"><span>Open order balances</span><strong>₱{{ number_format($outstandingBalance, 2) }}</strong><small>{{ $openOrderCount }} active orders</small></article>
        </div>

        <div class="business-alert-row">
            <a href="{{ route('admin.business.orders.index', ['status' => 'new_order']) }}" @class(['business-alert-card', 'is-alert' => $overdueOrderCount > 0])>
                <span>Overdue orders</span><strong>{{ $overdueOrderCount }}</strong><small>Orders past their deadline →</small>
            </a>
        </div>

        <div class="business-dashboard-columns">
            <section class="business-panel">
                <div class="business-panel-heading"><h2>Recent orders</h2><a href="{{ route('admin.business.orders.index') }}">All orders →</a></div>
                @forelse ($recentOrders as $order)
                    <a class="business-list-row" href="{{ route('admin.business.orders.show', $order) }}">
                        <span><strong>{{ $order->item_name }}</strong><small>{{ $order->order_number }} · {{ $order->customer->name }}</small></span>
                        <span class="business-status">{{ \App\Models\BusinessOrder::STATUSES[$order->status] ?? $order->status }}</span>
                    </a>
                @empty
                    <p class="staff-empty-state">No orders yet. Create an order or convert a quote inquiry.</p>
                @endforelse
            </section>

            <section class="business-panel">
                <div class="business-panel-heading"><h2>Top completed items</h2><a href="{{ route('admin.business.orders.index') }}">View orders →</a></div>
                @forelse ($topItems as $item)
                    <div class="business-list-row">
                        <span><strong>{{ $item->item_name }}</strong><small>{{ $item->order_count }} completed {{ \Illuminate\Support\Str::plural('order', $item->order_count) }}</small></span>
                        <strong>PHP (₱) {{ number_format((float) $item->sales_total, 2) }}</strong>
                    </div>
                @empty
                    <p class="staff-empty-state">Completed sales will appear here.</p>
                @endforelse
            </section>
        </div>
    </section>
@endsection
