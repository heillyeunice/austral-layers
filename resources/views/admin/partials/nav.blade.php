<nav class="staff-management-nav staff-site-nav" aria-label="Staff management">
    <a href="{{ route($staffHeaderUser->role === \App\Models\User::ROLE_ADMIN ? 'admin.dashboard' : 'superadmin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard', 'superadmin.dashboard')])>Dashboard</a>
    <a href="{{ route('admin.portfolio.index') }}" @class(['is-active' => request()->routeIs('admin.portfolio.*')])>Portfolio</a>
    <a href="{{ route('admin.products.index') }}" @class(['is-active' => request()->routeIs('admin.products.*')])>Products</a>
    <a href="{{ route('admin.inquiries.index') }}" @class(['is-active' => request()->routeIs('admin.inquiries.*', 'admin.notifications.show')])>Inquiries</a>
    <a href="{{ route('admin.business.dashboard') }}" @class(['is-active' => request()->routeIs('admin.business.*')])>Business Management</a>
    @if ($staffHeaderUser->role === \App\Models\User::ROLE_SUPERADMIN)
        <a href="{{ route('superadmin.site-settings.edit') }}" @class(['is-active' => request()->routeIs('superadmin.site-settings.*')])>Homepage &amp; branding</a>
    @endif
    <a href="{{ route('home') }}" target="_blank" rel="noopener">View website ↗</a>
</nav>
