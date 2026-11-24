@extends('layouts.app', [
    'pageTitle' => 'Payments — Austral Layers',
    'pageDescription' => 'Monitor recorded customer payments.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> Financial management</p><h1>Payments</h1><p>Review full and partial payments recorded against orders.</p></div></div>
        <div class="business-table-wrap">
            <table class="business-table">
                <thead><tr><th>Payment</th><th>Customer / order</th><th>Method</th><th>Reference</th><th>Recorded by</th><th></th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td><strong>PHP (₱) {{ number_format((float) $payment->amount, 2) }}</strong><small>{{ $payment->payment_date->format('M j, Y') }}</small></td>
                            <td><a href="{{ route('admin.business.orders.show', $payment->order) }}">{{ $payment->order->customer->name }}<small>{{ $payment->order->order_number }}</small></a></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td><td>{{ $payment->reference ?: '—' }}</td>
                            <td>{{ $payment->recordedBy?->name ?? '—' }}</td>
                            <td>@if($payment->proof_file_path)<a href="{{ route('admin.business.payments.proof', $payment) }}">View proof →</a>@endif</td>
                        </tr>
                    @empty<tr><td colspan="6">No payments recorded.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}

        <section class="business-panel business-outstanding-panel">
            <h2>Unpaid balances</h2>
            <div class="business-table-wrap">
                <table class="business-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Payment status</th><th>Deadline</th><th>Balance due</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($outstandingOrders as $order)
                            <tr>
                                <td><strong>{{ $order->item_name }}</strong><small>{{ $order->order_number }}</small></td>
                                <td>{{ $order->customer->name }}</td>
                                <td>{{ $order->payment_status }}</td>
                                <td @class(['business-overdue' => $order->deadline && $order->deadline->lt(today()) && !in_array($order->status, ['completed', 'cancelled'], true)])>{{ $order->deadline?->format('M j, Y') ?? 'No deadline' }}</td>
                                <td>PHP (₱) {{ number_format((float) $order->balance_due, 2) }}</td>
                                <td><a href="{{ route('admin.business.orders.show', $order) }}">Record payment →</a></td>
                            </tr>
                        @empty<tr><td colspan="6">No unpaid balances.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
