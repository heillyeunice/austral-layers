@extends('layouts.app', [
    'pageTitle' => $order->order_number.' — Orders — Austral Layers',
    'pageDescription' => 'Order details, payments, documents, and production history.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-order-page">
        @include('admin.business.nav')
        <a class="staff-auth-back" href="{{ route('admin.business.orders.index') }}">← Orders</a>
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> {{ $order->order_number }} · {{ $order->order_date->format('F j, Y') }} · {{ $statuses[$order->status] ?? $order->status }}</p><h1>{{ $order->item_name }}</h1><p><a href="{{ route('admin.business.customers.show', $order->customer) }}">{{ $order->customer->name }}</a>{{ $order->customer->company ? ' · '.$order->customer->company : '' }}</p></div></div>
        @if (session('business_saved'))<p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>@endif
        @foreach (['businessOrder', 'businessPayment', 'businessDocument', 'businessOrderArchive'] as $bag)
            @if ($errors->getBag($bag)->any())<div class="staff-auth-error" role="alert">@foreach ($errors->getBag($bag)->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @endforeach

        <div class="business-metric-grid business-order-metrics">
            <article class="business-metric-card">
                <span>Order total</span>
                <strong>
                    @if ((float) $order->total_price > 0)
                        ₱{{ number_format((float) $order->total_price, 2) }}
                    @else
                        Not set
                    @endif
                </strong>
            </article>
            <article class="business-metric-card"><span>Paid</span><strong>₱{{ number_format($paidAmount, 2) }}</strong></article>
            <article class="business-metric-card">
                <span>Balance due</span>
                <strong>
                    @if ((float) $order->total_price > 0)
                        ₱{{ number_format($balanceDue, 2) }}
                    @else
                        Not set
                    @endif
                </strong>
            </article>
            <article class="business-metric-card"><span>Payment status</span><strong>{{ $paymentStatus }}</strong></article>
        </div>

        <div class="business-dashboard-columns business-order-layout">
            <section class="business-panel business-order-details-panel">
                <h2>Order details</h2>
                <dl class="business-order-facts">
                    <div><dt>Product or service</dt><dd>{{ $order->item_name }}</dd></div>
                    <div><dt>Customer</dt><dd><a href="{{ route('admin.business.customers.show', $order->customer) }}">{{ $order->customer->name }}</a></dd></div>
                    <div><dt>Order date</dt><dd>{{ $order->order_date->format('M j, Y') }}</dd></div>
                    <div><dt>Deadline</dt><dd>{{ $order->deadline?->format('M j, Y') ?? 'Not set' }}</dd></div>
                    <div><dt>Total price</dt><dd>@if ((float) $order->total_price > 0) ₱{{ number_format((float) $order->total_price, 2) }} @else Not set @endif</dd></div>
                    <div><dt>Where was the order placed?</dt><dd>{{ \App\Models\BusinessOrder::SALES_CHANNELS[$order->sales_channel] ?? 'Not recorded' }}</dd></div>
                    <div><dt>Overall order owner</dt><dd>{{ $order->assignedStaff?->name ?? 'Unassigned' }}</dd></div>
                    <div class="business-order-fact-wide"><dt>Description</dt><dd>{{ $order->description }}</dd></div>
                    <div class="business-order-fact-wide"><dt>Specifications</dt><dd>{{ $order->specifications ?: 'None' }}</dd></div>
                </dl>
                <h3 class="business-subsection-heading">Production &amp; shipping</h3>
                <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ route('admin.business.orders.update', $order) }}">
                    @csrf @method('PUT')
                    <div class="staff-editor-grid">
                        <label>Order status <select name="status" required>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label>Shipping status <select name="shipping_status">@foreach (['not_shipped' => 'Not shipped', 'in_transit' => 'In transit', 'delivered' => 'Delivered', 'exception' => 'Exception'] as $value => $label)<option value="{{ $value }}" @selected(old('shipping_status', $order->shipping_status) === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label>Shipping method <input name="shipping_method" value="{{ old('shipping_method', $order->shipping_method) }}" maxlength="100"></label>
                        <label>Tracking number <input name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}" maxlength="120"></label>
                        <label class="staff-editor-wide">Add reference images or files
                            <input id="order-reference-files" type="file" name="reference_files[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple data-file-input>
                            <small data-file-input-status>No new files selected. {{ count($order->reference_files ?? []) }} existing reference {{ count($order->reference_files ?? []) === 1 ? 'file is' : 'files are' }} retained.</small>
                            <small>Up to five files total, 10 MB each.</small>
                        </label>
                        <label class="staff-editor-wide">Internal notes <textarea name="internal_notes" maxlength="4000">{{ old('internal_notes', $order->internal_notes) }}</textarea></label>
                    </div>
                    <button class="quote-button staff-auth-submit" type="submit">Save order <span>↗</span></button>
                </form>
            </section>
            <div class="business-stack">
                <section class="business-panel business-order-action-panel">
                    <h2>Record payment</h2>
                    @if ((float) $order->total_price > 0)
                    <p class="business-muted-copy">Remaining balance: ₱{{ number_format($balanceDue, 2) }}</p>
                    <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ route('admin.business.orders.payments.store', $order) }}" data-idempotent-form data-submitting-label="Recording payment...">
                        @csrf
                        <input type="hidden" name="submission_token" value="{{ old('submission_token', $paymentSubmissionToken) }}">
                        <label>Amount (₱) <input type="number" min="0.01" step="0.01" max="{{ $balanceDue }}" name="amount" value="{{ old('amount') }}" required></label>
                        <label>Payment method <select name="method" required>@foreach ($paymentMethods as $value => $label)<option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label>Payment date <input type="date" name="payment_date" value="{{ old('payment_date', today()->toDateString()) }}" required></label>
                        <label>Reference <input name="reference" value="{{ old('reference') }}" maxlength="150"></label>
                        <label>Proof of payment
                            <input id="payment-proof-file" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required data-file-input>
                            <small data-file-input-status>No proof selected.</small>
                        </label>
                        <label>Note <textarea name="note" maxlength="1000">{{ old('note') }}</textarea></label>
                        <button class="quote-button staff-auth-submit" type="submit" data-idempotent-submit>Record payment <span>＋</span></button>
                    </form>
                    @else
                        <p class="business-muted-copy">Set the agreed total in Order details before recording payments.</p>
                    @endif
                </section>
            </div>
        </div>

        <section class="business-panel business-order-action-panel business-document-workspace" data-document-workspace>
            <h2>Generate a document</h2>
            @if ((float) $order->total_price > 0)
                <fieldset class="business-document-type-picker" data-document-type-switch>
                    <legend>Choose what to generate</legend>
                    <label>
                        <input type="radio" name="document_choice" value="invoice" data-document-choice @checked(old('type', 'invoice') === 'invoice')>
                        <span>Invoice</span>
                    </label>
                    <label>
                        <input type="radio" name="document_choice" value="receipt" data-document-choice @checked(old('type') === 'receipt')>
                        <span>Receipt</span>
                    </label>
                </fieldset>

                <div class="business-document-form-panels">
                    <div data-document-panel="invoice" @if (old('type', 'invoice') !== 'invoice') hidden @endif>
                        <form class="staff-editor-form business-document-generator-form" method="post" action="{{ route('admin.business.orders.documents.store', $order) }}">
                            @csrf
                            <input type="hidden" name="type" value="invoice">
                            <label>
                                <span class="business-field-label">Invoice number</span>
                                <input name="invoice_number" value="{{ old('invoice_number') }}" maxlength="100" autocomplete="off">
                                <small>Leave blank to auto-generate a number. You can enter a number if you already have one.</small>
                            </label>
                            <label>Due date <input type="date" name="due_on" min="{{ today()->toDateString() }}"></label>
                            <button class="quote-button staff-auth-submit" type="submit">Generate invoice <span>↗</span></button>
                        </form>
                    </div>

                    <div data-document-panel="receipt" @if (old('type', 'invoice') !== 'receipt') hidden @endif>
                        @if ($invoices->isEmpty())
                            <p class="business-muted-copy">Generate an invoice before generating a receipt.</p>
                        @elseif ($availableReceiptPayments->isEmpty())
                            <p class="business-muted-copy">Record a payment first. Each payment can have one receipt.</p>
                        @else
                            <form class="staff-editor-form business-document-generator-form" method="post" action="{{ route('admin.business.orders.documents.store', $order) }}">
                                @csrf
                                <input type="hidden" name="type" value="receipt">
                                <label>Payment received
                                    <select name="payment_id" required>
                                        @foreach ($availableReceiptPayments as $payment)
                                            <option value="{{ $payment->id }}">₱{{ number_format((float) $payment->amount, 2) }} · {{ $paymentMethods[$payment->method] ?? $payment->method }} · {{ $payment->payment_date->format('M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Invoice number
                                    <select name="invoice_document_id" required>
                                        @foreach ($invoices as $invoice)
                                            <option value="{{ $invoice->id }}" @selected($loop->first)>{{ $invoice->invoice_number }} · {{ $invoice->issued_on->format('M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button class="quote-button staff-auth-submit" type="submit">Generate receipt <span>↗</span></button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <p class="business-muted-copy">Set the agreed total in Order details before issuing an invoice or receipt.</p>
            @endif

            @if ($order->documents->isNotEmpty())
                <div class="business-issued-documents">
                    <h3>Issued documents</h3>
                    @foreach ($order->documents as $document)
                        <a class="business-list-row" href="{{ route('admin.business.documents.show', $document) }}">
                            <span>
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
                                        Invoice no. {{ $document->invoiceDocument?->invoice_number }} · {{ $document->payment?->payment_date?->format('M j, Y') }}
                                    @elseif ($document->type === 'invoice')
                                        No invoice number · {{ $document->document_number }}
                                    @else
                                        {{ $document->document_number }}
                                    @endif
                                </small>
                            </span>
                            <span>Open →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <details class="business-archive-action" @if ($errors->getBag('businessOrderArchive')->any()) open @endif>
            <summary>Archive this order</summary>
            <p class="business-muted-copy">Archived orders are removed from active records and dashboard totals.</p>
            <form class="staff-editor-form" method="post" action="{{ route('admin.business.orders.archive', $order) }}" data-confirm-archive="Archive this order? Its payments and documents will no longer appear in active records.">
                @csrf
                <label>Reason for archiving <textarea name="reason" maxlength="2000" required>{{ old('reason') }}</textarea></label>
                <button class="quote-button staff-auth-submit" type="submit">Archive order</button>
            </form>
        </details>

        <div class="business-dashboard-columns business-history-columns">
            @if (($order->reference_files ?? []) || ($order->inquiry?->inspiration_files ?? []))
                <section class="business-panel">
                    <h2>Reference files</h2>
                    @foreach ($order->reference_files ?? [] as $index => $path)
                        <a class="business-list-row" href="{{ route('admin.business.orders.references.preview', [$order, $index]) }}"><span><strong>Order reference {{ $index + 1 }}</strong><small>{{ basename($path) }}</small></span><span>View →</span></a>
                    @endforeach
                    @foreach ($order->inquiry?->inspiration_files ?? [] as $index => $path)
                        <a class="business-list-row" href="{{ route('admin.inquiries.attachments.preview', [$order->inquiry, $index]) }}"><span><strong>Quote inspiration {{ $index + 1 }}</strong><small>{{ basename($path) }}</small></span><span>View →</span></a>
                    @endforeach
                </section>
            @endif
            <section class="business-panel">
                <h2>Payment history</h2>
                @forelse ($order->payments as $payment)
                    <div class="business-list-row"><span><strong>₱{{ number_format((float) $payment->amount, 2) }} · {{ $paymentMethods[$payment->method] ?? $payment->method }}</strong><small>{{ $payment->payment_date->format('M j, Y') }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</small></span>@if ($payment->proof_file_path)<a href="{{ route('admin.business.payments.proof', [$payment, 'from' => 'order']) }}">View proof →</a>@endif</div>
                @empty<p class="staff-empty-state">No payments recorded.</p>@endforelse
            </section>
            <section class="business-panel">
                <h2>Order progress</h2>
                @foreach ($order->statusHistory as $history)
                    <div class="business-list-row"><span><strong>{{ $statuses[$history->to_status] ?? $history->to_status }}</strong><small>{{ $history->created_at->format('M j, Y · g:i A') }} · {{ $history->changedBy?->name ?? 'System' }}</small></span></div>
                @endforeach
            </section>
            <section class="business-panel">
                <h2>Production assignment history</h2>
                @forelse ($order->stageAssignmentHistory as $history)
                    <div class="business-list-row">
                        <span>
                            <strong>{{ $statuses[$history->stage] ?? $history->stage }} · {{ $history->assigned_staff_name ?? 'Unassigned' }}</strong>
                            <small>{{ $history->created_at->format('M j, Y · g:i A') }} · Updated by {{ $history->changed_by_name ?? 'Actor not recorded' }}</small>
                        </span>
                    </div>
                @empty
                    <p class="staff-empty-state">No production staff assignments recorded.</p>
                @endforelse
            </section>
        </div>
    </section>
@endsection
