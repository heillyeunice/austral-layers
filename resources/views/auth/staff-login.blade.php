@extends('layouts.app', [
    'pageTitle' => $roleLabel . ' Login — Austral Layers',
    'pageDescription' => 'Staff sign in for Austral Layers.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-auth-page">
        <a class="staff-auth-back" href="{{ route('home') }}">← Back to the website</a>
        <div class="staff-auth-card">
            <p class="eyebrow"><span></span> {{ $roleLabel }} access</p>
            <h1>{{ $roleLabel }} sign in</h1>
            <p class="staff-auth-intro">Sign in with your Austral Layers staff account.</p>

            @if ($errors->getBag('staffLogin')->any())
                <p class="staff-auth-error" role="alert">Sign-in failed. Check your email and password, then try again.</p>
            @endif

            <form class="staff-auth-form" method="post" action="{{ route($role . '.login.store') }}">
                @csrf
                <label for="staff-email">Email address</label>
                <input id="staff-email" name="login_email" type="email" value="{{ old('login_email') }}" autocomplete="username" required>

                <label for="staff-password">Password</label>
                <input id="staff-password" name="login_password" type="password" autocomplete="current-password" required>

                <button class="quote-button staff-auth-submit" type="submit">Sign in <span>↗</span></button>
            </form>

            <p class="staff-auth-switch">
                @if ($role === 'admin')
                    Need SuperAdmin access? <a href="{{ route('superadmin.login') }}">SuperAdmin sign in ↗</a>
                @else
                    Need Admin access? <a href="{{ route('admin.login') }}">Admin sign in ↗</a>
                @endif
            </p>
        </div>
    </section>
@endsection
