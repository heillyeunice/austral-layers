@extends('layouts.app', [
    'pageTitle' => 'My profile — Austral Layers',
    'pageDescription' => 'Manage your Austral Layers staff profile.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Account</p>
                <h1>My profile</h1>
                <p>Your name and contact details are visible to other authorized staff.</p>
            </div>
        </div>

        @if (session('staff_profile_saved'))
            <p class="staff-admin-success" role="status">{{ session('staff_profile_saved') }}</p>
        @endif
        @if ($errors->getBag('staffProfile')->any())
            <div class="staff-auth-error" role="alert">
                @foreach ($errors->getBag('staffProfile')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="staff-profile-layout">
            <aside class="staff-profile-card">
                <div class="staff-profile-large-avatar">
                    <img
                        data-profile-photo-preview
                        src="{{ $user->profile_photo_path ? route('admin.profile.photo') : '' }}"
                        alt=""
                        @if (! $user->profile_photo_path) hidden @endif
                    >
                    <span data-profile-photo-initial @if ($user->profile_photo_path) hidden @endif>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
                </div>
                <form class="staff-photo-upload-form" method="post" enctype="multipart/form-data" action="{{ route('admin.profile.photo.update') }}">
                    @csrf
                    <input class="staff-photo-input" id="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" aria-describedby="profile-photo-help profile-photo-file-name">
                    <label class="staff-photo-change" for="profile-photo-input">Choose photo</label>
                    <small id="profile-photo-help">JPG, PNG, or WEBP · Maximum 5 MB</small>
                    <p class="staff-photo-file-name" id="profile-photo-file-name" role="status">No new photo selected.</p>
                    <button class="staff-photo-submit" type="submit">Update photo</button>
                </form>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                @if ($user->position)
                    <p>{{ $user->position }}</p>
                @endif
                <span class="staff-profile-role">{{ $user->role === \App\Models\User::ROLE_SUPERADMIN ? 'SuperAdmin' : 'Admin' }}</span>
                <dl>
                    <div><dt>Account type</dt><dd>{{ $user->role === \App\Models\User::ROLE_SUPERADMIN ? 'SuperAdmin' : 'Admin' }}</dd></div>
                    <div><dt>Member since</dt><dd>{{ $user->created_at?->format('F Y') ?? '—' }}</dd></div>
                </dl>
            </aside>

            <form class="staff-editor-form staff-profile-form" method="post" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

                <fieldset class="staff-settings-section">
                    <legend>Profile details</legend>
                    <label>Full name
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    </label>
                    <label>Email address
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                    </label>
                </fieldset>

                <fieldset class="staff-settings-section">
                    <legend>Change password</legend>
                    <p class="staff-muted-copy">Leave these fields blank to keep your current password.</p>
                    <label>Current password
                        <input type="password" name="current_password" autocomplete="current-password">
                    </label>
                    <label>New password
                        <input type="password" name="password" autocomplete="new-password" minlength="12">
                    </label>
                    <label>Confirm new password
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12">
                    </label>
                </fieldset>

                <div class="staff-editor-actions">
                    <button class="quote-button staff-auth-submit" type="submit">Save profile <span>↗</span></button>
                </div>
            </form>
        </div>
    </section>
@endsection
