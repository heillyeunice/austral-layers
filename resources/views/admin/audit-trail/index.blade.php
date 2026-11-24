@extends('layouts.app', [
    'pageTitle' => 'Audit trail — Austral Layers',
    'pageDescription' => 'Review administrative changes made in Austral Layers.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page audit-trail-page">
        <a class="staff-auth-back" href="{{ route('superadmin.dashboard') }}">← SuperAdmin dashboard</a>
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> SuperAdmin</p>
                <h1>Audit trail</h1>
                <p>Administrative changes with the person who made them and when. Logins and page views are not recorded.</p>
            </div>
        </div>

        <form class="audit-trail-filters" method="get" action="{{ route('superadmin.audit.index') }}">
            <label>
                <span>Search records</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, record, or type">
            </label>
            <label>
                <span>Changed by</span>
                <select name="actor_id">
                    <option value="">All staff</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Action</span>
                <select name="action">
                    <option value="">All actions</option>
                    @foreach (['created', 'updated', 'deleted', 'restored'] as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ \Illuminate\Support\Str::headline($action) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="quote-button" type="submit">Filter</button>
            @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                <a class="audit-trail-clear" href="{{ route('superadmin.audit.index') }}">Clear</a>
            @endif
        </form>

        <div class="business-table-wrap">
            <table class="business-table audit-trail-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('M j, Y · g:i A') }}</td>
                            <td>
                                <strong>{{ $log->actor_name }}</strong>
                                <small>{{ \Illuminate\Support\Str::headline($log->actor_role) }}</small>
                            </td>
                            <td>{{ \Illuminate\Support\Str::headline($log->action) }}</td>
                            <td>
                                <strong>{{ $log->subject_label }}</strong>
                                <small>{{ \Illuminate\Support\Str::headline(class_basename($log->subject_type)) }} #{{ $log->subject_id }}</small>
                            </td>
                            <td>
                                @if ($log->changes)
                                    <details class="audit-trail-change-details">
                                        <summary>{{ count($log->changes) }} {{ \Illuminate\Support\Str::plural('field', count($log->changes)) }}</summary>
                                        <ul class="audit-trail-change-list">
                                            @foreach ($log->changes as $field => $change)
                                                <li>
                                                    <strong>{{ \Illuminate\Support\Str::headline($field) }}</strong>
                                                    <span>
                                                        {{ $change['old'] ?? '—' }}
                                                        <span aria-hidden="true">→</span>
                                                        {{ $change['new'] ?? '—' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No audit entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
@endsection
