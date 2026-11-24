<header @class(['site-header', 'staff-site-header' => $staffHeaderUser])>
    <a
        class="brand"
        href="{{ $staffHeaderUser ? route($staffHeaderUser->role === \App\Models\User::ROLE_SUPERADMIN ? 'superadmin.dashboard' : 'admin.dashboard') : route('home') }}"
        aria-label="{{ $staffHeaderUser ? 'Austral Layers staff dashboard' : 'Austral Layers home' }}"
    ><img src="{{ asset($siteSettings['logo_path']) }}" alt="" class="brand-logo"><span>AUSTRAL <small>LAYERS</small></span></a>
    @if ($staffHeaderUser)
        @include('admin.partials.nav')
        <div class="staff-header-tools">
            <button class="staff-theme-toggle" type="button" data-staff-theme-toggle aria-label="Switch to light mode" title="Switch to light mode" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true" data-theme-icon="sun"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
                <svg viewBox="0 0 24 24" aria-hidden="true" data-theme-icon="moon"><path d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8 8.5 8.5 0 1 0 20.2 15.6Z"></path></svg>
            </button>
            <details class="staff-header-menu staff-notification-menu">
                <summary class="staff-notification-trigger" aria-label="Notifications, {{ $staffUnreadNotificationCount }} unread">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></svg>
                    @if ($staffUnreadNotificationCount > 0)
                        <span class="staff-header-badge">{{ $staffUnreadNotificationCount > 99 ? '99+' : $staffUnreadNotificationCount }}</span>
                    @endif
                </summary>
                <div class="staff-header-popover staff-notification-popover">
                    <div class="staff-popover-heading">
                        <strong>Notifications</strong>
                        @if ($staffUnreadNotificationCount > 0)
                            <span>{{ $staffUnreadNotificationCount }} new</span>
                        @endif
                    </div>
                    @forelse ($staffNotifications as $notification)
                        <a class="staff-popover-notification" href="{{ route('admin.notifications.show', $notification->id) }}">
                            <strong>New inquiry from {{ $notification->data['customer_name'] ?? 'a customer' }}</strong>
                            <small>{{ $notification->data['service'] ?? 'Project request' }} · {{ $notification->created_at->diffForHumans() }}</small>
                        </a>
                    @empty
                        <p class="staff-popover-empty">You’re all caught up.</p>
                    @endforelse
                    <a class="staff-popover-footer" href="{{ route('admin.inquiries.index') }}">All inquiries <span>↗</span></a>
                </div>
            </details>

            <details class="staff-header-menu staff-profile-menu">
                <summary class="staff-avatar-trigger" aria-label="Profile and account settings">
                    @if ($staffHeaderUser->profile_photo_path)
                        <img src="{{ route('admin.profile.photo') }}" alt="">
                    @else
                        <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($staffHeaderUser->name, 0, 1)) }}</span>
                    @endif
                </summary>
                <div class="staff-header-popover staff-profile-popover">
                    <div class="staff-profile-summary">
                        <strong>{{ $staffHeaderUser->name }}</strong>
                        <small>{{ $staffHeaderUser->email }}</small>
                        @if ($staffHeaderUser->position)
                            <small>{{ $staffHeaderUser->position }}</small>
                        @endif
                        <span>{{ $staffHeaderUser->role === \App\Models\User::ROLE_SUPERADMIN ? 'SuperAdmin' : 'Admin' }}</span>
                    </div>
                    <a class="staff-profile-link" href="{{ route('admin.profile.edit') }}#profile-photo-input">Change profile photo</a>
                    <a class="staff-profile-link" href="{{ route('admin.profile.edit') }}">My profile</a>
                    <form method="post" action="{{ route($staffHeaderUser->role === \App\Models\User::ROLE_SUPERADMIN ? 'superadmin.logout' : 'admin.logout') }}">
                        @csrf
                        <button class="staff-profile-logout" type="submit">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    @else
        <nav class="nav-links" aria-label="Primary navigation">
            <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
            @if (($siteSettings['show_portfolio'] ?? '1') === '1')
                <a href="{{ route('portfolio') }}" @class(['is-active' => request()->routeIs('portfolio')]) @if(request()->routeIs('portfolio')) aria-current="page" @endif>Portfolio</a>
            @endif
            @if (($siteSettings['show_products'] ?? '1') === '1')
                <a href="{{ route('products.index') }}" @class(['is-active' => request()->routeIs('products.*')]) @if(request()->routeIs('products.*')) aria-current="page" @endif>Products</a>
            @endif
            <a href="{{ route('home') }}#services">Services</a>
            <a href="{{ route('home') }}#studio">Studio</a>
        </nav>
        <button class="quote-button" type="button" data-open-quote>Request a quote <span>↗</span></button>
    @endif
</header>
