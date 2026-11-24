@extends('layouts.app', [
    'pageTitle' => $roleLabel . ' Dashboard — Austral Layers',
    'pageDescription' => $roleLabel . ' area for Austral Layers staff.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard">
        <p class="eyebrow"><span></span> Secure area</p>
        <h1>{{ $roleLabel }} dashboard</h1>
        <p>Signed in as {{ auth()->user()->name }}.</p>

        @if ($roleLabel === 'Admin' || $myTasks->isNotEmpty())
            <section class="staff-my-tasks" aria-labelledby="my-tasks-title">
                <header class="staff-my-tasks-heading">
                    <div>
                        <h2 id="my-tasks-title">My Tasks</h2>
                        <p>Ongoing work assigned to you</p>
                    </div>
                    <span class="staff-my-tasks-count">{{ $myTasks->count() }} active</span>
                </header>

                <div class="staff-my-task-list">
                    @forelse ($myTasks as $task)
                        <a class="staff-my-task-row" href="{{ route('admin.business.orders.show', $task->order) }}">
                            <span class="staff-my-task-main">
                                <strong>{{ $task->order->item_name }}</strong>
                                <small>{{ $task->order->order_number }} · {{ $task->order->customer->name }}</small>
                            </span>
                            <span class="staff-my-task-stage">{{ \App\Models\BusinessOrder::STATUSES[$task->stage] ?? $task->stage }}</span>
                            <span class="staff-my-task-deadline">
                                <small>Deadline</small>
                                <strong>{{ $task->order->deadline?->format('M j, Y') ?? 'Not set' }}</strong>
                            </span>
                        </a>
                    @empty
                        <p class="staff-my-tasks-empty">No ongoing tasks are currently assigned to you.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <nav class="staff-tools" aria-label="Website management">
            <a href="{{ route('admin.portfolio.index') }}">
                <span>Portfolio</span>
                <small>Projects, galleries, and keywords</small>
                <b aria-hidden="true">↗</b>
            </a>
            <a href="{{ route('admin.products.index') }}">
                <span>Products</span>
                <small>Product photos and order links</small>
                <b aria-hidden="true">↗</b>
            </a>
            <a href="{{ route('admin.inquiries.index') }}">
                <span>Quote inquiries</span>
                <small>Read and update customer requests</small>
                <b aria-hidden="true">↗</b>
            </a>
            <a href="{{ route('admin.business.dashboard') }}">
                <span>Business Management</span>
                <small>Orders, customers, payments, and expenses</small>
                <b aria-hidden="true">↗</b>
            </a>
            @if (auth()->user()->role === \App\Models\User::ROLE_SUPERADMIN)
                <a href="{{ route('superadmin.audit.index') }}">
                    <span>Audit trail</span>
                    <small>Review administrative changes</small>
                    <b aria-hidden="true">↗</b>
                </a>
                <a href="{{ route('superadmin.site-settings.edit') }}">
                    <span>Homepage &amp; branding</span>
                    <small>Update studio copy, logo, and accent color</small>
                    <b aria-hidden="true">↗</b>
                </a>
            @endif
        </nav>

        @if ($roleLabel === 'SuperAdmin')
            <section class="staff-admin-management" aria-labelledby="admin-nomination-title">
                <p class="eyebrow"><span></span> Staff access</p>
                <h2 id="admin-nomination-title">Nominate an Admin</h2>
                <p>Only SuperAdmin can create Admin accounts. Set a unique email and password, then share the sign-in details privately.</p>

                @if (session('admin_nominated'))
                    <p class="staff-admin-success" role="status">{{ session('admin_nominated') }} has been added as an Admin.</p>
                @endif

                @if ($errors->getBag('adminNomination')->any())
                    <div class="staff-auth-error" role="alert">
                        @foreach ($errors->getBag('adminNomination')->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form class="staff-admin-form" method="post" action="{{ route('superadmin.admins.store') }}">
                    @csrf
                    <label for="admin-name">Admin name</label>
                    <input id="admin-name" name="admin_name" type="text" value="{{ old('admin_name') }}" autocomplete="name" required>

                    <label for="admin-email">Admin email</label>
                    <input id="admin-email" name="admin_email" type="email" value="{{ old('admin_email') }}" autocomplete="email" required>

                    <label for="admin-position">Admin position / job title</label>
                    <input id="admin-position" name="admin_position" type="text" value="{{ old('admin_position') }}" maxlength="120" autocomplete="organization-title" required>

                    <label for="admin-password">Initial password (at least 12 characters)</label>
                    <input id="admin-password" name="admin_password" type="password" autocomplete="new-password" required>

                    <label for="admin-password-confirmation">Confirm initial password</label>
                    <input id="admin-password-confirmation" name="admin_password_confirmation" type="password" autocomplete="new-password" required>

                    <button class="quote-button staff-auth-submit" type="submit">Nominate Admin <span>↗</span></button>
                </form>
            </section>
        @endif
    </section>
@endsection
