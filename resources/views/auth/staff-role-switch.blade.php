@extends('layouts.app', [
    'pageTitle' => 'Switch staff access — Austral Layers',
    'pageDescription' => 'Confirm sign out before switching staff accounts.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-auth-page">
        <a class="staff-auth-back" href="{{ $currentDashboard }}">← Keep using {{ $currentRoleLabel }}</a>
        <div class="staff-auth-card">
            <p class="eyebrow"><span></span> Switch staff access</p>
            <h1>Sign out first?</h1>
            <p class="staff-auth-intro">You’re signed in as {{ $currentRoleLabel }}. To continue to {{ $roleLabel }} sign in, you’ll need to log out of this account.</p>

            <form class="staff-auth-form" method="post" action="{{ route('staff.logout-and-switch', $role) }}">
                @csrf
                <button class="quote-button staff-auth-submit" type="submit">Sign out and continue <span>↗</span></button>
            </form>
        </div>
    </section>
@endsection
