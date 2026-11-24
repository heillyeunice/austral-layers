@extends('layouts.app', [
    'pageTitle' => 'Quote inquiries — Austral Layers',
    'pageDescription' => 'View Austral Layers quote inquiries.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Customer requests</p>
                <h1>Quote inquiries</h1>
                <p>New quote requests and their uploaded inspiration files appear here.</p>
            </div>
        </div>

        <form class="staff-filter-form" method="get" action="{{ route('admin.inquiries.index') }}">
            <label for="inquiry-status">Filter by status</label>
            <select id="inquiry-status" name="status">
                <option value="" @selected($status === null)>All inquiries</option>
                @foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit">Filter</button>
        </form>

        <div class="staff-content-list">
            @forelse ($inquiries as $inquiry)
                <a class="staff-inquiry-row" href="{{ route('admin.inquiries.show', $inquiry) }}">
                    <span class="staff-inquiry-status status-{{ $inquiry->status }}">{{ $inquiry->status }}</span>
                    <span class="staff-inquiry-summary">
                        <strong>{{ $inquiry->name }}</strong>
                        <small>{{ $inquiry->service }} · {{ $inquiry->quantity }}</small>
                    </span>
                    <span class="staff-inquiry-email">{{ $inquiry->email }}</span>
                    <time datetime="{{ $inquiry->created_at->toAtomString() }}">{{ $inquiry->created_at->format('M j, Y') }}</time>
                    <span aria-hidden="true">↗</span>
                </a>
            @empty
                <p class="staff-empty-state">No quote inquiries yet.</p>
            @endforelse
        </div>

        {{ $inquiries->links() }}
    </section>
@endsection
