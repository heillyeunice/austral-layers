@extends('layouts.app', [
    'pageTitle' => ($project ? 'Edit portfolio project' : 'Add portfolio project') . ' — Austral Layers',
    'pageDescription' => 'Manage a portfolio project.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">

        <a class="staff-auth-back" href="{{ route('admin.portfolio.index') }}">← Portfolio projects</a>
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Portfolio</p>
                <h1>{{ $project ? 'Edit project' : 'Add a project' }}</h1>
            </div>
        </div>

        @if ($errors->getBag('portfolioContent')->any())
            <div class="staff-auth-error" role="alert">
                @foreach ($errors->getBag('portfolioContent')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ $project ? route('admin.portfolio.update', $project->slug) : route('admin.portfolio.store') }}">
            @csrf
            @if ($project)
                @method('PUT')
            @endif

            <div class="staff-editor-grid">
                <label>Project title
                    <input type="text" name="title" value="{{ old('title', $project?->title) }}" required maxlength="180">
                </label>
                <label>URL slug
                    <input type="text" name="slug" value="{{ old('slug', $project?->slug) }}" required maxlength="180" pattern="[A-Za-z0-9_-]+">
                </label>
            </div>

            <label>Project status
                <select name="status" required>
                    @foreach (['Commissioned', 'In-house'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $project?->status ?? 'Commissioned') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            <label>Keywords <span>(separate with commas)</span>
                <input type="text" name="keywords" value="{{ old('keywords', implode(', ', $project?->keywords ?? [])) }}" maxlength="500" placeholder="Printed, Fine detail, Custom modeling">
            </label>

            <label>Project description
                <textarea name="description" rows="4" maxlength="2000" required>{{ old('description', $project?->description) }}</textarea>
            </label>

            <label>What we did
                <textarea name="work" rows="4" maxlength="2000" required>{{ old('work', $project?->work) }}</textarea>
            </label>

            @if ($project && count($project->images ?? []))
                <div class="staff-existing-images">
                    <p>Current gallery</p>
                    <div>
                        @foreach ($project->images as $image)
                            <img src="{{ asset($image['src']) }}" alt="{{ $image['alt'] ?? $project->title }}">
                        @endforeach
                    </div>
                </div>
            @endif

            <label>Project photos
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/heic,image/heif" multiple {{ $project ? '' : 'required' }}>
                <small>{{ $project ? 'Choose photos only if you want to replace the current gallery.' : 'Add at least one photo. ' }}Up to 12 images, 20 MB each.</small>
            </label>

            <div class="staff-editor-actions">
                <button class="quote-button staff-auth-submit" type="submit">{{ $project ? 'Save project' : 'Create project' }} <span>↗</span></button>
                <a class="staff-cancel-link" href="{{ route('admin.portfolio.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
