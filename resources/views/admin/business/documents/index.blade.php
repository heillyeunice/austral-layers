@extends('layouts.app', [
    'pageTitle' => 'Invoices & receipts — Austral Layers',
    'pageDescription' => 'Issued invoices and payment receipts.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> Sales &amp; operations</p><h1>Invoices &amp; receipts</h1><p>Enter an invoice number or leave it blank to auto-generate one. Each receipt records a payment against an invoice.</p></div></div>
        <div class="business-table-wrap">
            <table class="business-table">
                <thead><tr><th>Document</th><th>Customer</th><th>Order</th><th>Issued</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>
                                <strong>
                                    @if ($document->type === 'invoice')
                                        {{ $document->invoice_number ? 'Invoice' : 'Legacy invoice' }}
                                    @elseif ($document->type === 'receipt')
                                        Receipt
                                    @else
                                        Legacy quotation
                                    @endif
                                </strong>
                                <small>
                                    @if ($document->type === 'invoice' && $document->invoice_number)
                                        Invoice no. {{ $document->invoice_number }}
                                    @elseif ($document->type === 'receipt')
                                        Invoice no. {{ $document->invoiceDocument?->invoice_number }}
                                    @elseif ($document->type === 'invoice')
                                        No invoice number · {{ $document->document_number }}
                                    @else
                                        {{ $document->document_number }}
                                    @endif
                                </small>
                            </td>
                            <td>{{ $document->order->customer->name }}</td>
                            <td>{{ $document->order->order_number }}</td>
                            <td>{{ $document->issued_on->format('M j, Y') }}</td>
                            <td>₱{{ number_format((float) ($document->type === 'receipt' ? data_get($document->snapshot, 'payment_amount', 0) : data_get($document->snapshot, 'total_price', 0)), 2) }}</td>
                            <td><a class="staff-content-edit" href="{{ route('admin.business.documents.show', $document) }}">View <span>↗</span></a></td>
                        </tr>
                    @empty<tr><td colspan="6">No documents yet. Create one from an order.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $documents->links() }}
    </section>
@endsection
