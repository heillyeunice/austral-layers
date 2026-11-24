@extends('layouts.app', [
    'pageTitle' => 'Homepage & branding — Austral Layers',
    'pageDescription' => 'Manage Austral Layers homepage copy and visual branding.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> SuperAdmin settings</p>
                <h1>Homepage &amp; branding</h1>
                <p>Changes here are public. Portfolio, product, and inquiry controls are available to Admins too.</p>
            </div>
        </div>

        @if (session('site_settings_saved'))
            <p class="staff-admin-success" role="status">{{ session('site_settings_saved') }}</p>
        @endif
        @if ($errors->getBag('siteSettings')->any())
            <div class="staff-auth-error" role="alert">
                @foreach ($errors->getBag('siteSettings')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ route('superadmin.site-settings.update') }}">
            @csrf
            @method('PUT')

            <fieldset class="staff-settings-section">
                <legend>Branding</legend>
                <label>Brand tagline
                    <input type="text" name="brand_tagline" value="{{ old('brand_tagline', $settings['brand_tagline']) }}" maxlength="200" required>
                </label>
                <label>Main color 1
                    <input class="staff-color-input" type="color" name="brand_primary" value="{{ old('brand_primary', $settings['brand_primary']) }}" required>
                    <small>Used for the main page background. Choose a darker shade to keep light text readable.</small>
                </label>
                <input type="hidden" name="use_second_main_color" value="0">
                <label class="staff-color-toggle">
                    <input id="use-second-main-color" type="checkbox" name="use_second_main_color" value="1" @checked(old('use_second_main_color', filled($settings['brand_secondary'])))>
                    Use a second main color
                </label>
                <label>Second main color
                    <input class="staff-color-input" id="brand-secondary-color" type="color" name="brand_secondary" value="{{ old('brand_secondary', $settings['brand_secondary'] ?: '#1B2B29') }}" @if (filled($settings['brand_secondary'])) required @endif>
                    <small>Optional. Used for panels and cards; choose a darker shade for readable text.</small>
                </label>
                <label>Accent color
                    <input class="staff-color-input" type="color" name="brand_accent" value="{{ old('brand_accent', $settings['brand_accent']) }}" required>
                    <small>Used for highlights, buttons, and links.</small>
                </label>
                <div class="staff-logo-preview">
                    <img src="{{ asset($settings['logo_path']) }}" alt="Current Austral Layers logo">
                    <label>Replace logo
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                        <small>PNG, JPG, or WEBP · Maximum 5 MB.</small>
                    </label>
                </div>
            </fieldset>

            <fieldset class="staff-settings-section">
                <legend>Public sections</legend>
                <p>Choose which sections visitors can see. Manage their content in the Portfolio and Products areas.</p>
                <input type="hidden" name="show_portfolio" value="0">
                <label class="staff-color-toggle">
                    <input type="checkbox" name="show_portfolio" value="1" @checked(old('show_portfolio', $settings['show_portfolio']) === '1')>
                    Show Portfolio in the website
                </label>
                <input type="hidden" name="show_products" value="0">
                <label class="staff-color-toggle">
                    <input type="checkbox" name="show_products" value="1" @checked(old('show_products', $settings['show_products']) === '1')>
                    Show Products in the website
                </label>
            </fieldset>

            <fieldset class="staff-settings-section">
                <legend>Hero section</legend>
                <label>Eyebrow
                    <input type="text" name="hero_eyebrow" value="{{ old('hero_eyebrow', $settings['hero_eyebrow']) }}" maxlength="200" required>
                </label>
                <div class="staff-editor-grid">
                    <label>Headline, line one
                        <input type="text" name="hero_title_first" value="{{ old('hero_title_first', $settings['hero_title_first']) }}" maxlength="200" required>
                    </label>
                    <label>Headline, line two
                        <input type="text" name="hero_title_second" value="{{ old('hero_title_second', $settings['hero_title_second']) }}" maxlength="200" required>
                    </label>
                </div>
                <label>Hero tagline
                    <input type="text" name="hero_tagline" value="{{ old('hero_tagline', $settings['hero_tagline']) }}" maxlength="200" required>
                </label>
                <label>Introduction
                    <textarea name="hero_description" rows="3" maxlength="2000" required>{{ old('hero_description', $settings['hero_description']) }}</textarea>
                </label>
            </fieldset>

            <fieldset class="staff-settings-section">
                <legend>Services</legend>
                <div class="staff-editor-grid">
                    <label>Section heading, line one
                        <input type="text" name="services_heading_first" value="{{ old('services_heading_first', $settings['services_heading_first']) }}" maxlength="200" required>
                    </label>
                    <label>Section heading, line two
                        <input type="text" name="services_heading_second" value="{{ old('services_heading_second', $settings['services_heading_second']) }}" maxlength="200" required>
                    </label>
                </div>
                @foreach ([1, 2, 3] as $number)
                    <div class="staff-settings-subsection">
                        <p>Service {{ $number }}</p>
                        <label>Title
                            <input type="text" name="service_{{ $number }}_title" value="{{ old('service_' . $number . '_title', $settings['service_' . $number . '_title']) }}" maxlength="200" required>
                        </label>
                        <label>Description
                            <textarea name="service_{{ $number }}_description" rows="3" maxlength="2000" required>{{ old('service_' . $number . '_description', $settings['service_' . $number . '_description']) }}</textarea>
                        </label>
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="staff-settings-section">
                <legend>Studio section</legend>
                <div class="staff-editor-grid">
                    <label>Heading, line one
                        <input type="text" name="studio_heading_first" value="{{ old('studio_heading_first', $settings['studio_heading_first']) }}" maxlength="200" required>
                    </label>
                    <label>Heading, line two
                        <input type="text" name="studio_heading_second" value="{{ old('studio_heading_second', $settings['studio_heading_second']) }}" maxlength="200" required>
                    </label>
                </div>
                <label>Paragraph one
                    <textarea name="studio_paragraph_one" rows="3" maxlength="2000" required>{{ old('studio_paragraph_one', $settings['studio_paragraph_one']) }}</textarea>
                </label>
                <label>Paragraph two
                    <textarea name="studio_paragraph_two" rows="3" maxlength="2000" required>{{ old('studio_paragraph_two', $settings['studio_paragraph_two']) }}</textarea>
                </label>
                <label>Closing line
                    <input type="text" name="studio_signoff" value="{{ old('studio_signoff', $settings['studio_signoff']) }}" maxlength="200" required>
                </label>
            </fieldset>

            <fieldset class="staff-settings-section">
                <legend>Quote invitation</legend>
                <div class="staff-editor-grid">
                    <label>Heading, line one
                        <input type="text" name="inquiry_heading_first" value="{{ old('inquiry_heading_first', $settings['inquiry_heading_first']) }}" maxlength="200" required>
                    </label>
                    <label>Heading, line two
                        <input type="text" name="inquiry_heading_second" value="{{ old('inquiry_heading_second', $settings['inquiry_heading_second']) }}" maxlength="200" required>
                    </label>
                </div>
                <label>Invitation text
                    <textarea name="inquiry_description" rows="3" maxlength="2000" required>{{ old('inquiry_description', $settings['inquiry_description']) }}</textarea>
                </label>
            </fieldset>

            <div class="staff-editor-actions">
                <button class="quote-button staff-auth-submit" type="submit">Save homepage &amp; branding <span>↗</span></button>
            </div>
        </form>
    </section>
@endsection
