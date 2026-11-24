@extends('layouts.app', [
    'pageTitle' => 'Portfolio — Austral Layers',
    'pageDescription' => 'Explore custom 3D modeling and 3D printed work from Austral Layers.',
])

@section('content')
    <section class="inner-hero portfolio-hero">
        <p class="eyebrow"><span></span> Portfolio · Selected work</p>
        <h1>Modelled in CAD.<br><em>Printed layer by layer.</em></h1>
        <p>From fine-detail builds we print in-house to commissioned 3D models and objects made to order, explore the work behind Austral Layers.</p>
    </section>

    <dl class="portfolio-stats" aria-label="Portfolio at a glance">
        <div><dt>Projects</dt><dd>{{ $stats['projects'] }}</dd></div>
        <div><dt>Commissioned</dt><dd>{{ $stats['commissioned'] }}</dd></div>
        <div><dt>0.2 mm builds</dt><dd>{{ $stats['fine_detail'] }}</dd></div>
        <div><dt>CAD models</dt><dd>{{ $stats['cad_models'] }}</dd></div>
    </dl>

    <section class="portfolio-work">
        <div class="portfolio-work-heading">
            <div>
                <p class="eyebrow"><span></span> The projects</p>
                <h2>Selected work</h2>
            </div>
            <p>{{ $stats['projects'] }} projects · Click a project to see its gallery and details.</p>
        </div>

        <div class="portfolio-work-grid">
            @foreach ($projects as $project)
                <a class="portfolio-work-card" href="{{ route('portfolio.show', $project['slug']) }}">
                    <span class="portfolio-card-image">
                        <img src="{{ asset($project['images'][0]['src']) }}" alt="{{ $project['images'][0]['alt'] }}" loading="lazy">
                        @if (count($project['images']) > 1)
                            <span class="portfolio-photo-count">{{ count($project['images']) }} photos</span>
                        @endif
                        <span class="portfolio-card-arrow" aria-hidden="true">↗</span>
                    </span>
                    <span class="portfolio-card-copy">
                        <span class="portfolio-card-meta">
                            <span class="portfolio-status">{{ $project['status'] }}</span>
                            @foreach ($project['keywords'] as $keyword)
                                <span>{{ $keyword }}</span>
                            @endforeach
                        </span>
                        <span class="portfolio-card-title">{{ $project['title'] }}</span>
                        <span class="portfolio-card-description">{{ $project['description'] }}</span>
                        <span class="portfolio-card-link">View project <span aria-hidden="true">↗</span></span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="page-cta">
        <p class="eyebrow"><span></span> Have an idea of your own?</p>
        <h2>Let’s make it<br>real.</h2>
        <button class="quote-button" type="button" data-open-quote>Request a quote <span>↗</span></button>
    </section>
@endsection
