@extends('layouts.app', [
    'pageTitle' => $project['title'] . ' — Austral Layers Portfolio',
    'pageDescription' => $project['description'],
])

@section('content')
    <section class="portfolio-detail">
        <a class="portfolio-back" href="{{ route('portfolio') }}">← All projects</a>

        <div class="portfolio-detail-grid">
            <div class="portfolio-gallery" data-portfolio-gallery>
                <figure class="portfolio-gallery-main">
                    <img
                        src="{{ asset($project['images'][0]['src']) }}"
                        alt="{{ $project['images'][0]['alt'] }}"
                        data-gallery-main
                    >
                    <figcaption data-gallery-caption aria-live="polite">{{ $project['images'][0]['caption'] }}</figcaption>
                </figure>

                @if (count($project['images']) > 1)
                    <div class="portfolio-gallery-thumbs" aria-label="More project photos">
                        @foreach ($project['images'] as $index => $image)
                            <button
                                type="button"
                                class="portfolio-gallery-thumb"
                                data-gallery-thumb
                                data-gallery-src="{{ asset($image['src']) }}"
                                data-gallery-alt="{{ $image['alt'] }}"
                                data-gallery-caption="{{ $image['caption'] }}"
                                aria-label="View photo: {{ $image['caption'] }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            >
                                <img src="{{ asset($image['src']) }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="portfolio-detail-copy">
                <p class="eyebrow"><span></span> {{ $project['status'] }}</p>
                <h1>{{ $project['title'] }}</h1>
                <p class="portfolio-detail-description">{{ $project['description'] }}</p>

                <section class="portfolio-work-note">
                    <p class="portfolio-detail-label">What we did</p>
                    <p>{{ $project['work'] }}</p>
                </section>

                <div class="portfolio-keywords" aria-label="Project keywords">
                    @foreach ($project['keywords'] as $keyword)
                        <span>{{ $keyword }}</span>
                    @endforeach
                </div>

                <button class="quote-button portfolio-detail-quote" type="button" data-open-quote>Have a similar idea? <span>Request a quote ↗</span></button>
            </div>
        </div>
    </section>

    <section class="portfolio-next">
        <p class="eyebrow"><span></span> Keep exploring</p>
        <a href="{{ route('portfolio') }}">Back to all projects <span aria-hidden="true">↗</span></a>
    </section>
@endsection
