@extends('layouts.app', [
    'pageTitle' => 'Archive — Austral Layers',
    'pageDescription' => 'Review archived orders and expenses, including the reasons they were archived.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Business records</p>
                <h1>Archive</h1>
                <p>Archived orders and expenses are excluded from active records and dashboard totals.</p>
            </div>
        </div>
        @if (session('business_saved'))<p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>@endif

        <section class="business-panel">
            <h2>Archived orders</h2>
            <div class="business-table-wrap">
                <table class="business-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Archived</th><th>Archived by</th><th>Reason</th></tr></thead>
                    <tbody>
                        @forelse ($archivedOrders as $order)
                            <tr>
                                <td><strong>{{ $order->item_name }}</strong><small>{{ $order->order_number }}</small></td>
                                <td>{{ $order->customer?->name ?? 'Customer unavailable' }}</td>
                                <td>{{ $order->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td>{{ $order->deletedBy?->name ?? '—' }}</td>
                                <td>{{ $order->deletion_reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No archived orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $archivedOrders->links() }}
        </section>

        <section class="business-panel">
            <h2>Archived expenses</h2>
            <div class="business-table-wrap">
                <table class="business-table">
                    <thead><tr><th>Expense</th><th>Amount</th><th>Archived</th><th>Archived by</th><th>Reason</th></tr></thead>
                    <tbody>
                        @forelse ($archivedExpenses as $expense)
                            <tr>
                                <td><strong>{{ $expense->title }}</strong><small>{{ $expense->expense_date->format('M j, Y') }}</small></td>
                                <td>PHP (₱) {{ number_format((float) $expense->amount, 2) }}</td>
                                <td>{{ $expense->deleted_at?->format('M j, Y g:i A') }}</td>
                                <td>{{ $expense->deletedBy?->name ?? '—' }}</td>
                                <td>{{ $expense->deletion_reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No archived expenses.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $archivedExpenses->links() }}
        </section>
    </section>
@endsection
