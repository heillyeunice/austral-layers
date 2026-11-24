@extends('layouts.app', [
    'pageTitle' => 'Orders — Austral Layers',
    'pageDescription' => 'Track business orders and production progress.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div><p class="eyebrow"><span></span> Sales &amp; operations</p><h1>Orders &amp; projects</h1><p>Track each order from confirmation through delivery.</p></div>
            <a class="quote-button staff-content-action" href="{{ route('admin.business.orders.create') }}">Create order <span>＋</span></a>
        </div>
        <section class="business-tracker" aria-labelledby="order-tracker-title">
            <div class="business-panel-heading">
                <h2 id="order-tracker-title">Production tracking</h2>
                <span>Assign staff from Modeling through Shipped. Past stages are fixed; changes are recorded.</span>
            </div>
            <p class="business-tracker-status" data-tracker-status role="status" aria-live="polite"></p>
            <div class="business-tracker-stage-targets" data-order-tracker aria-label="Production stages">
                @foreach ($productionStatuses as $value => $label)
                    @php($stageCount = $kanbanOrders->where('status', $value)->count())
                    <div class="business-tracker-stage" data-status-target="{{ $value }}" aria-label="Drop an order here to move it to {{ $label }}; {{ $stageCount }} orders">
                        <span>{{ $label }}</span>
                        <b data-stage-count="{{ $value }}">{{ $stageCount }}</b>
                    </div>
                @endforeach
            </div>
            <div class="business-tracker-orders">
                @foreach ($kanbanOrders as $order)
                    @php($currentStep = array_search($order->status, array_keys($productionStatuses), true))
                    <article class="business-tracker-card" draggable="true" data-order-card data-status="{{ $order->status }}" data-furthest-status-index="{{ $order->furthest_status_index }}" data-status-url="{{ route('admin.business.orders.status', $order) }}" data-order-url="{{ route('admin.business.orders.show', $order) }}" title="Drag to a stage to update progress; double-click to open order">
                        <div class="business-tracker-card-heading">
                            <a class="business-tracker-order-link" href="{{ route('admin.business.orders.show', $order) }}">
                                <strong>{{ $order->item_name }}</strong>
                                <small>{{ $order->order_number }}</small>
                            </a>
                            <span class="business-tracker-customer">{{ $order->customer->name }}</span>
                            <span class="business-tracker-channel">{{ $salesChannels[$order->sales_channel] ?? 'Not recorded' }}</span>
                            <span class="business-tracker-deadline">{{ $order->deadline?->format('M j, Y') ?? 'No deadline' }}</span>
                            <strong class="business-tracker-price">
                                @if ((float) $order->total_price > 0)
                                    PHP (₱) {{ number_format((float) $order->total_price, 2) }}
                                @else
                                    Price not set
                                @endif
                            </strong>
                            <label class="business-tracker-select-label">Stage
                                <select data-order-status-select aria-label="Stage for {{ $order->item_name }}">
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($order->status === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="business-order-progress" role="list" aria-label="Progress for {{ $order->item_name }}">
                            @foreach ($productionStatuses as $stepValue => $stepLabel)
                                @php($stepIndex = array_search($stepValue, array_keys($productionStatuses), true))
                                @php($statusIndex = array_search($stepValue, array_keys($statuses), true))
                                @php($stageAssignment = $order->stageAssignments->firstWhere('stage', $stepValue))
                                @php($isStageLocked = $statusIndex < $order->furthest_status_index)
                                <div @class([
                                    'business-order-progress-step',
                                    'is-complete' => $stepIndex < $currentStep,
                                    'is-current' => $stepIndex === $currentStep,
                                ]) data-progress-step="{{ $stepValue }}" data-stage-index="{{ $statusIndex }}" role="listitem" @if ($stepIndex === $currentStep) aria-current="step" @endif>
                                    <span class="business-order-progress-dot" aria-hidden="true"></span>
                                    <span class="business-order-progress-label">{{ $stepLabel }}</span>
                                    @if (in_array($stepValue, \App\Models\BusinessOrder::ASSIGNABLE_STAGES, true))
                                        <label class="business-order-stage-assignee">
                                            <span>{{ $isStageLocked ? 'Fixed' : 'Staff' }}</span>
                                            <select
                                                data-stage-assignee-select
                                                data-stage="{{ $stepValue }}"
                                                data-assignment-url="{{ route('admin.business.orders.stage-assignment', $order) }}"
                                                data-saved-assignee="{{ $stageAssignment?->assigned_staff_id ?? '' }}"
                                                aria-label="Staff assigned to {{ $stepLabel }} for {{ $order->item_name }}"
                                                @disabled($isStageLocked)
                                            >
                                                <option value="">Unassigned</option>
                                                @foreach ($staff as $user)
                                                    <option value="{{ $user->id }}" @selected((string) $stageAssignment?->assigned_staff_id === (string) $user->id)>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        <section class="business-order-list" aria-labelledby="order-list-title">
            <div class="business-panel-heading"><h2 id="order-list-title">Order list</h2><span>All orders, including completed and cancelled</span></div>
            <form class="staff-filter-form business-filter-form" method="get" action="{{ route('admin.business.orders.index') }}">
                <label for="order-status-filter">Filter by status</label>
                <select id="order-status-filter" name="status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="in_progress" @selected($status === 'in_progress')>All orders in progress</option>
                    @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                </select>
                <label for="order-channel-filter">Where ordered</label>
                <select id="order-channel-filter" name="channel" onchange="this.form.submit()">
                    <option value="">All channels</option>
                    @foreach ($salesChannels as $value => $label)<option value="{{ $value }}" @selected($channel === $value)>{{ $label }}</option>@endforeach
                </select>
            </form>
            <div class="business-table-wrap">
                <table class="business-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Where ordered</th><th>Status</th><th>Deadline</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>{{ $order->item_name }}</strong><small>{{ $order->order_number }} · {{ $order->order_date->format('M j, Y') }}</small></td>
                                <td>{{ $order->customer->name }}</td>
                                <td>{{ $salesChannels[$order->sales_channel] ?? 'Not recorded' }}</td>
                                <td><span class="business-status">{{ $statuses[$order->status] ?? $order->status }}</span></td>
                                <td>{{ $order->deadline?->format('M j, Y') ?? '—' }}</td>
                                <td>
                                    @if ((float) $order->total_price > 0)
                                        PHP (₱) {{ number_format((float) $order->total_price, 2) }}
                                    @else
                                        Not set
                                    @endif
                                </td>
                                <td><a class="staff-content-edit" href="{{ route('admin.business.orders.show', $order) }}">Open <span>↗</span></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </section>
    </section>
@endsection
