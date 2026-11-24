@extends('layouts.app', [
    'pageTitle' => 'Attachment preview — Austral Layers',
    'pageDescription' => 'Private staff attachment preview.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page attachment-preview-page">
        <div class="attachment-preview-toolbar">
            <div>
                <p class="eyebrow"><span></span> Private attachment</p>
                <h1>{{ $fileName }}</h1>
            </div>
            <div class="attachment-preview-actions">
                <a class="staff-file-link" href="{{ $downloadUrl }}">Download</a>
                <a class="attachment-preview-close" href="{{ $returnUrl }}" aria-label="Close attachment preview" title="Close preview">×</a>
            </div>
        </div>
        <div class="attachment-preview-stage">
            @if (str_starts_with($mimeType, 'image/'))
                <img src="{{ $contentUrl }}" alt="{{ $fileName }}">
            @elseif ($mimeType === 'application/pdf')
                <iframe src="{{ $contentUrl }}" title="Preview {{ $fileName }}"></iframe>
            @else
                <p>Preview is not available for this file type.</p>
                <a class="staff-file-link" href="{{ $downloadUrl }}">Download file</a>
            @endif
        </div>
    </section>
@endsection
