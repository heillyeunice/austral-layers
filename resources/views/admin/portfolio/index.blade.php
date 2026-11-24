@extends('layouts.app', [
    'pageTitle' => 'Manage portfolio — Austral Layers',
    'pageDescription' => 'Manage Austral Layers portfolio projects.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Website content</p>
                <h1>Portfolio projects</h1>
                <p>Update project details, commissioned status, photos, and keywords.</p>
            </div>
            <a class="quote-button staff-content-action" href="{{ route('admin.portfolio.create') }}">Add project <span>＋</span></a>
        </div>

        @if (session('portfolio_saved'))
            <p class="staff-admin-success" role="status">{{ session('portfolio_saved') }}</p>
        @endif

        <div class="staff-content-list">
            @forelse ($projects as $project)
                <article class="staff-content-row">
                    <div class="staff-content-thumb">
                        @if (!empty($project->images[0]['src']))
                            <img src="{{ asset($project->images[0]['src']) }}" alt="">
                        @else
                            <span>No photo</span>
                        @endif
                    </div>
                    <div class="staff-content-summary">
                        <p>{{ $project->status }}</p>
                        <h2>{{ $project->title }}</h2>
                        <span>{{ implode(' · ', $project->keywords ?? []) }}</span>
                    </div>
                    <a class="staff-content-edit" href="{{ route('admin.portfolio.edit', $project->slug) }}">Edit <span>↗</span></a>
                    <form method="post" action="{{ route('admin.portfolio.destroy', $project->slug) }}" onsubmit="return confirm('Delete this portfolio project?')">
                        @csrf
                        @method('DELETE')
                        <button class="staff-content-delete" type="submit">Delete</button>
                    </form>
                </article>
            @empty
                <p class="staff-empty-state">No portfolio projects yet.</p>
            @endforelse
        </div>

        {{ $projects->links() }}
    </section>
@endsection
