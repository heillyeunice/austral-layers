@extends('layouts.app', [
    'pageTitle' => 'Austral Layers — Custom 3D Printing & Modeling',
    'pageDescription' => 'Austral Layers is a custom 3D printing and modeling studio. Down to layer up?',
])

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><span></span> {{ $siteSettings['hero_eyebrow'] }}</p>
            <h1>{{ $siteSettings['hero_title_first'] }}<br>{{ $siteSettings['hero_title_second'] }}</h1>
            <p class="hero-tagline">{{ $siteSettings['hero_tagline'] }}</p>
        </div>
        <div class="hero-side">
            <p>{{ $siteSettings['hero_description'] }}</p>
            @if (($siteSettings['show_portfolio'] ?? '1') === '1' || ($siteSettings['show_products'] ?? '1') === '1')
                <a class="text-link" href="{{ route(($siteSettings['show_portfolio'] ?? '1') === '1' ? 'portfolio' : 'products.index') }}">Explore what we make <span>↓</span></a>
            @endif
        </div>
        <div class="hero-word" aria-hidden="true">LAYERS</div>
    </section>

    @if (($siteSettings['show_products'] ?? '1') === '1' || ($siteSettings['show_portfolio'] ?? '1') === '1')
    <section class="work-section" id="work">
        <div class="section-heading"><p>{{ ($siteSettings['show_products'] ?? '1') === '1' ? 'From our printers' : 'Selected portfolio work' }}</p><p>A growing collection of things we’ve made.</p></div>
        <div class="project-grid">
            @if (($siteSettings['show_products'] ?? '1') === '1')
            <article class="project">
                <a href="{{ route('products.show', 'custom-plate-number-keychain') }}" class="project-image" aria-label="View Custom Plate Number Keychain">
                    <img src="/images/personalized-keychains.png" alt="Austral Layers personalized plate keychains, shown with custom license plates and model cars">
                    <span class="project-arrow">↗</span>
                </a>
                <div class="project-meta">
                    <h2>01 — Personalized Plate Keychains</h2>
                    <div class="project-details"><p>Everyday objects, made a little more personal.</p><span>Custom Accessories · 3D Printed</span></div>
                </div>
            </article>
            <article class="project">
                <a href="{{ route('products.show', 'wall-mounted-automotive-key-holder') }}" class="project-image" aria-label="View Wall Mounted Automotive Key Holder">
                    <img src="/images/automotive-key-holders.png" alt="Austral Layers automotive key holders displaying Audi, Toyota, Ford, and Porsche designs">
                    <span class="project-arrow">↗</span>
                </a>
                <div class="project-meta">
                    <h2>02 — Automotive Key Holders</h2>
                    <div class="project-details"><p>Familiar cars, reimagined as functional wall pieces.</p><span>Custom Modeling · Home Accessories</span></div>
                </div>
            </article>
            @endif
            @if (($siteSettings['show_portfolio'] ?? '1') === '1')
            <article class="project">
                <a href="{{ route('portfolio') }}" class="project-image" aria-label="Explore custom-made Austral Layers creations">
                    <img src="/images/custom-made-products.png" alt="A collage of custom 3D-printed wheel accessories, billiard badges, and personalized pieces">
                    <span class="project-arrow">↗</span>
                </a>
                <div class="project-meta">
                    <h2>03 — Personalized Creations</h2>
                    <div class="project-details"><p>Small details that make something entirely yours.</p><span>Custom Products · Made to Order</span></div>
                </div>
            </article>
            @endif
        </div>
        <div class="section-cta">
            @if (($siteSettings['show_portfolio'] ?? '1') === '1')
                <a class="text-link" href="{{ route('portfolio') }}">View all portfolio work <span>↗</span></a>
            @endif
            @if (($siteSettings['show_products'] ?? '1') === '1')
                <a class="text-link" href="{{ route('products.index') }}">View all products <span>↗</span></a>
            @endif
        </div>
    </section>
    @endif

    <section class="services" id="services">
        <div class="services-intro">
            <p class="eyebrow light"><span></span> What we do</p>
            <h2>{{ $siteSettings['services_heading_first'] }}<br>{{ $siteSettings['services_heading_second'] }}</h2>
        </div>
        <div class="service-list">
            @foreach ([1, 2, 3] as $number)
                <article><span>0{{ $number }}</span><div><h3>{{ $siteSettings['service_' . $number . '_title'] }}</h3><p>{{ $siteSettings['service_' . $number . '_description'] }}</p></div></article>
            @endforeach
        </div>
    </section>

    <section class="studio" id="studio">
        <div class="studio-heading">
            <p class="eyebrow"><span></span> The studio</p>
            <h2>{{ $siteSettings['studio_heading_first'] }}<br><em>{{ $siteSettings['studio_heading_second'] }}</em></h2>
        </div>
        <div class="studio-content">
            <div class="studio-copy">
                <p>{{ $siteSettings['studio_paragraph_one'] }}</p>
                <p>{{ $siteSettings['studio_paragraph_two'] }}</p>
                <p class="studio-signoff">{{ $siteSettings['studio_signoff'] }}</p>
            </div>
            <button class="studio-cta" type="button" data-open-quote>Let’s make something <span>↗</span></button>
        </div>
    </section>

    <section class="inquiry" id="inquiry">
        <div>
            <p class="eyebrow"><span></span> Have something in mind?</p>
            <h2>{{ $siteSettings['inquiry_heading_first'] }}<br>{{ $siteSettings['inquiry_heading_second'] }}</h2>
        </div>
        <p class="inquiry-copy">{{ $siteSettings['inquiry_description'] }}</p>
        <button class="text-link inquiry-button" type="button" data-open-quote>Request a quote <span>↗</span></button>
    </section>
@endsection
