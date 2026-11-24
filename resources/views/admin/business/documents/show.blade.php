@extends('layouts.app', [
    'pageTitle' => ($document->type === 'receipt' ? 'Receipt '.$document->document_number : ($document->invoice_number ?? $document->document_number)).' — Austral Layers',
    'pageDescription' => $document->type === 'receipt' ? 'Printable payment receipt.' : 'Printable invoice.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-document-page @if ($document->type === 'receipt') business-document-page--receipt @endif">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span>
                    @if ($document->type === 'receipt')
                        Receipt {{ $document->document_number }} · Invoice {{ $document->invoiceDocument->invoice_number }}
                    @elseif ($document->invoice_number)
                        Invoice no. {{ $document->invoice_number }}
                    @else
                        {{ $document->document_number }}
                    @endif
                </p>
                <h1>{{ $document->type === 'receipt' ? 'Receipt' : ($document->type === 'invoice' && $document->invoice_number ? 'Invoice' : 'Legacy document') }}</h1>
                <p>Issued {{ $document->issued_on->format('F j, Y') }}@if ($document->due_on) · Due {{ $document->due_on->format('F j, Y') }}@endif</p>
            </div>
            <button class="quote-button staff-content-action" type="button" onclick="window.print()">
                {{ $document->type === 'receipt' ? 'Print receipt' : 'Print invoice' }} <span>↗</span>
            </button>
        </div>

        @if ($document->type === 'receipt')
            <article class="business-document business-document-receipt">
                <header>
                    <strong>AUSTRAL LAYERS</strong>
                    <strong>PAYMENT RECEIPT</strong>
                </header>
                <div class="business-receipt-identifiers">
                    <p><span>Receipt reference</span><strong>{{ $document->document_number }}</strong></p>
                    <p><span>Invoice number</span><strong>{{ $document->invoiceDocument->invoice_number }}</strong></p>
                    <p><span>Date received</span><strong>{{ $document->payment->payment_date->format('M j, Y') }}</strong></p>
                </div>
                <div class="business-receipt-customer">
                    <span>Received from</span>
                    <strong>{{ data_get($document->snapshot, 'customer_name') }}</strong>
                    @if (data_get($document->snapshot, 'customer_company'))
                        <small>{{ data_get($document->snapshot, 'customer_company') }}</small>
                    @endif
                </div>
                <div class="business-receipt-description">
                    <span>Payment for</span>
                    <strong>{{ data_get($document->snapshot, 'item_name') }}</strong>
                    <small>Order {{ data_get($document->snapshot, 'order_number') }}</small>
                </div>
                <div class="business-receipt-payment">
                    <span>Payment method</span>
                    <strong>{{ data_get($document->snapshot, 'payment_method') }}</strong>
                    @if (data_get($document->snapshot, 'payment_reference'))
                        <small>Reference: {{ data_get($document->snapshot, 'payment_reference') }}</small>
                    @endif
                </div>
                <footer>
                    <span>AMOUNT RECEIVED</span>
                    <strong>₱{{ number_format((float) data_get($document->snapshot, 'payment_amount', 0), 2) }}</strong>
                </footer>
                <p class="business-receipt-thanks">Thank you for choosing Austral Layers!</p>
            </article>
        @elseif ($document->type === 'invoice' && $document->invoice_number)
            @php($total = (float) data_get($document->snapshot, 'total_price', 0))
            @php($half = round($total / 2, 2))
            <article class="business-document business-document-invoice">
                <header>
                    <strong>AUSTRAL LAYERS</strong>
                    <strong>INVOICE</strong>
                    <span>INVOICE NO. {{ $document->invoice_number }}</span>
                </header>
                <div class="business-document-parties">
                    <div>
                        <small>Prepared for</small>
                        <h2>{{ data_get($document->snapshot, 'customer_name') }}</h2>
                        @if (data_get($document->snapshot, 'customer_company'))
                            <p>{{ data_get($document->snapshot, 'customer_company') }}</p>
                        @endif
                        @if (data_get($document->snapshot, 'customer_email'))
                            <p>{{ data_get($document->snapshot, 'customer_email') }}</p>
                        @endif
                    </div>
                    <div>
                        <small>Order reference</small>
                        <h2>{{ data_get($document->snapshot, 'order_number') }}</h2>
                        <p>Issued {{ $document->issued_on->format('M j, Y') }}</p>
                        @if ($document->due_on)
                            <p>Due {{ $document->due_on->format('M j, Y') }}</p>
                        @endif
                    </div>
                </div>
                <section class="business-document-order-summary">
                    <p>Here’s a summary of your order for confirmation. 😊</p>
                    <h2>ORDER SUMMARY</h2>
                    <dl>
                        <div><dt>Order Name</dt><dd>{{ data_get($document->snapshot, 'item_name') }}</dd></div>
                        @if (data_get($document->snapshot, 'description'))
                            <div><dt>Order details</dt><dd class="business-document-preserved-text">{{ data_get($document->snapshot, 'description') }}</dd></div>
                        @endif
                        @if (data_get($document->snapshot, 'specifications'))
                            <div><dt>Specifications</dt><dd class="business-document-preserved-text">{{ data_get($document->snapshot, 'specifications') }}</dd></div>
                        @endif
                        <div><dt>Total Amount</dt><dd>₱{{ number_format($total, 2) }}</dd></div>
                        <div><dt>50% Down Payment</dt><dd>₱{{ number_format($half, 2) }}</dd></div>
                        <div><dt>Remaining Balance after down payment</dt><dd>₱{{ number_format($half, 2) }} + shipping fee</dd></div>
                    </dl>
                </section>
                <section class="business-document-terms">
                    <p>Please review all the details and let us know if everything is correct.</p>
                    <p>Just a quick reminder of our payment terms:</p>
                    <ul>
                        <li>A 50% down payment is required to officially confirm and process your order.</li>
                        <li>The remaining 50% balance must be settled before we ship or dispatch your order.</li>
                        <li>Production will proceed once the down payment has been received.</li>
                    </ul>
                    <p>Once payment is sent, kindly send us a screenshot of your payment confirmation.</p>
                    <p>Thank you for choosing Austral Layers! We appreciate your trust and support. 💙</p>
                </section>
                <footer>
                    <span>Total invoice amount</span>
                    <strong>₱{{ number_format($total, 2) }}</strong>
                </footer>
            </article>
        @else
            <article class="business-document">
                <header>
                    <span>AUSTRAL LAYERS</span>
                    <span>{{ $document->type === 'quotation' ? 'LEGACY QUOTATION' : 'LEGACY INVOICE' }} · {{ $document->document_number }}</span>
                </header>
                <div class="business-document-parties">
                    <div>
                        <small>Prepared for</small>
                        <h2>{{ data_get($document->snapshot, 'customer_name') }}</h2>
                        @if (data_get($document->snapshot, 'customer_company'))
                            <p>{{ data_get($document->snapshot, 'customer_company') }}</p>
                        @endif
                        <p>{{ data_get($document->snapshot, 'customer_email') }}</p>
                    </div>
                    <div>
                        <small>Order reference</small>
                        <h2>{{ data_get($document->snapshot, 'order_number') }}</h2>
                        <p>Issued {{ $document->issued_on->format('M j, Y') }}</p>
                    </div>
                </div>
                <div class="business-document-line">
                    <span>
                        <strong>{{ data_get($document->snapshot, 'item_name') }}</strong>
                        <small>{{ data_get($document->snapshot, 'description') }}</small>
                        @if (data_get($document->snapshot, 'specifications'))
                            <small>Specifications: {{ data_get($document->snapshot, 'specifications') }}</small>
                        @endif
                    </span>
                    <strong>₱{{ number_format((float) data_get($document->snapshot, 'total_price', 0), 2) }}</strong>
                </div>
                <footer>
                    <span>Total</span>
                    <strong>₱{{ number_format((float) data_get($document->snapshot, 'total_price', 0), 2) }}</strong>
                </footer>
            </article>
        @endif

        <a class="staff-auth-back" href="{{ route('admin.business.orders.show', $document->order) }}">← Back to order</a>
    </section>
@endsection
