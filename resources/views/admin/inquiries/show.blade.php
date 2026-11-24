@extends('layouts.app', [
    'pageTitle' => 'Quote inquiry — Austral Layers',
    'pageDescription' => 'Review a customer quote inquiry.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">

        <a class="staff-auth-back" href="{{ route('admin.inquiries.index') }}">← Quote inquiries</a>
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> {{ $inquiry->created_at->format('F j, Y · g:i A') }}</p>
                <h1>{{ $inquiry->name }}</h1>
                <p><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>@if($inquiry->company) · {{ $inquiry->company }}@endif</p>
            </div>
        </div>

        @if (session('inquiry_updated'))
            <p class="staff-admin-success" role="status">{{ session('inquiry_updated') }}</p>
        @endif
        @if ($errors->getBag('inquiryStatus')->any())
            <div class="staff-auth-error" role="alert">
                @foreach ($errors->getBag('inquiryStatus')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="staff-inquiry-detail-grid">
            <section class="staff-inquiry-detail">
                <p class="staff-content-kicker">Project details</p>
                <dl>
                    <div><dt>Service</dt><dd>{{ $inquiry->service }}</dd></div>
                    <div><dt>Quantity</dt><dd>{{ $inquiry->quantity }}</dd></div>
                    @if ($inquiry->social_media_link)
                        <div>
                            <dt>Social media contact</dt>
                            <dd><a href="{{ $inquiry->social_media_link }}" target="_blank" rel="noopener noreferrer">{{ $inquiry->social_media_link }}</a></dd>
                        </div>
                    @endif
                    <div><dt>Project brief</dt><dd class="staff-inquiry-message">{{ $inquiry->message }}</dd></div>
                </dl>
            </section>

            <section class="staff-inquiry-detail">
                <p class="staff-content-kicker">Manage request</p>
                <form class="staff-editor-form" method="post" action="{{ route('admin.inquiries.update', $inquiry) }}">
                    @csrf
                    @method('PATCH')
                    <label>Status
                        <select name="status" required>
                            @foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $inquiry->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="quote-button staff-auth-submit" type="submit">Save status <span>↗</span></button>
                </form>

                @if ($inquiry->businessOrder)
                    @if ($inquiry->businessOrder->trashed())
                        <a class="staff-file-link" href="{{ route('admin.business.archive.index') }}">Linked order is archived · View archive <span>↗</span></a>
                    @else
                        <a class="staff-file-link" href="{{ route('admin.business.orders.show', $inquiry->businessOrder) }}">Open linked business order <span>↗</span></a>
                    @endif
                @else
                    <form class="staff-editor-form" method="post" action="{{ route('admin.business.orders.from-inquiry', $inquiry) }}">
                        @csrf
                        <button class="quote-button staff-auth-submit" type="submit">Convert to business order <span>＋</span></button>
                    </form>
                    <p class="staff-muted-copy staff-inquiry-convert-help">You can set the agreed total from the order details after conversion.</p>
                @endif

                <p class="staff-content-kicker staff-files-kicker">Inspiration files</p>
                @forelse ($inquiry->inspiration_files ?? [] as $index => $path)
                    <a class="staff-file-link" href="{{ route('admin.inquiries.attachments.preview', [$inquiry, $index]) }}">View inspiration {{ $index + 1 }} <span>↗</span></a>
                @empty
                    <p class="staff-muted-copy">No files were attached.</p>
                @endforelse
            </section>
        </div>
    </section>
@endsection
