<nav class="staff-management-nav business-module-nav" aria-label="Business management">
    <a href="{{ route('admin.business.dashboard') }}" @class(['is-active' => request()->routeIs('admin.business.dashboard')])>Overview</a>
    <a href="{{ route('admin.business.orders.index') }}" @class(['is-active' => request()->routeIs('admin.business.orders.*')])>Orders</a>
    <a href="{{ route('admin.business.customers.index') }}" @class(['is-active' => request()->routeIs('admin.business.customers.*')])>Customers</a>
    <a href="{{ route('admin.business.documents.index') }}" @class(['is-active' => request()->routeIs('admin.business.documents.*')])>Invoices &amp; receipts</a>
    <a href="{{ route('admin.business.payments.index') }}" @class(['is-active' => request()->routeIs('admin.business.payments.*')])>Payments</a>
    <a href="{{ route('admin.business.expenses.index') }}" @class(['is-active' => request()->routeIs('admin.business.expenses.*')])>Expenses</a>
    <a href="{{ route('admin.business.reports.index') }}" @class(['is-active' => request()->routeIs('admin.business.reports.*')])>Reports</a>
    <a href="{{ route('admin.business.print-calculator.index') }}" @class(['is-active' => request()->routeIs('admin.business.print-calculator.*')])>3D print calculator</a>
    <a href="{{ route('admin.business.archive.index') }}" @class(['is-active' => request()->routeIs('admin.business.archive.*')])>Archive</a>
</nav>
