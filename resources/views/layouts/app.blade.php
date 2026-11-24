<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $pageDescription ?? 'Austral Layers is a custom 3D printing and modeling studio. Down to layer up?' }}">
    @if ($noIndex ?? false)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>{{ $pageTitle ?? 'Austral Layers — Custom 3D Printing &amp; Modeling' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['staff-backend' => request()->routeIs('admin.*', 'superadmin.*')]) data-theme="dark" style="--paper: {{ $siteSettings['brand_primary'] ?? '#14201F' }}; --surface: {{ filled($siteSettings['brand_secondary'] ?? null) ? $siteSettings['brand_secondary'] : ($siteSettings['brand_primary'] ?? '#14201F') }}; --orange: {{ $siteSettings['brand_accent'] ?? '#FF5A1F' }}">
    @include('partials.site-header')
    <main>
        @yield('content')
    </main>
    @include('partials.site-footer')
    @include('partials.quote-modal')
    @if (session('quote_success'))
        <div class="success-toast" role="status">Thanks, {{ session('quote_name') }}. We’ll be in touch shortly.</div>
    @endif
</body>
</html>
