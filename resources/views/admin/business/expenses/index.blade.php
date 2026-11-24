@extends('layouts.app', [
    'pageTitle' => 'Expenses — Austral Layers',
    'pageDescription' => 'Record expenses and review business costs.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page">
        @include('admin.business.nav')
        <div class="staff-content-heading"><div><p class="eyebrow"><span></span> Financial management</p><h1>Expenses</h1><p>Current year recorded expenses: <strong>PHP (₱) {{ number_format($yearTotal, 2) }}</strong></p></div></div>
        @if (session('business_saved'))<p class="staff-admin-success" role="status">{{ session('business_saved') }}</p>@endif
        @if ($errors->getBag('businessExpense')->any())
            <div class="staff-auth-error" role="alert">@foreach ($errors->getBag('businessExpense')->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        @if ($errors->getBag('businessExpenseArchive')->any())
            <div class="staff-auth-error" role="alert">@foreach ($errors->getBag('businessExpenseArchive')->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <div class="business-dashboard-columns business-form-layout">
            <section class="business-panel">
                <h2>Record expense</h2>
                <form class="staff-editor-form" method="post" enctype="multipart/form-data" action="{{ route('admin.business.expenses.store') }}" data-idempotent-form data-submitting-label="Recording expense...">
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
                    <label>Description <input name="title" value="{{ old('title') }}" maxlength="180" required></label>
                    <label>Category <select name="category" required>@foreach ($categories as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Amount (PHP / ₱) <input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount') }}" required></label>
                    <label>Date <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}" required></label>
                    <label class="business-checkbox-label"><input type="checkbox" name="recurring" value="1" @checked(old('recurring'))> Recurring expense</label>
                    <label>Receipt / proof
                        <input id="expense-receipt-file" type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" data-file-input>
                        <small data-file-input-status>No receipt selected (optional).</small>
                    </label>
                    <label>Notes <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                    <button class="quote-button staff-auth-submit" type="submit" data-idempotent-submit>Record expense <span>＋</span></button>
                </form>
            </section>
            <section class="business-panel">
                <h2>Expense history</h2>
                <div class="business-table-wrap">
                    <table class="business-table">
                        <thead><tr><th>Expense</th><th>Category</th><th>Amount</th><th>Receipt</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td><strong>{{ $expense->title }}</strong><small>{{ $expense->expense_date->format('M j, Y') }}{{ $expense->recurring ? ' · Recurring' : '' }}</small></td>
                                    <td>{{ $categories[$expense->category] ?? $expense->category }}</td>
                                    <td>PHP (₱) {{ number_format((float) $expense->amount, 2) }}</td>
                                    <td>@if($expense->receipt_file_path)<a href="{{ route('admin.business.expenses.receipt', $expense) }}">Receipt →</a>@endif</td>
                                    <td>
                                        <details>
                                            <summary>Archive</summary>
                                            <form class="staff-editor-form" method="post" action="{{ route('admin.business.expenses.archive', $expense) }}" data-confirm-archive="Archive this expense? It will be removed from active records and dashboard totals.">
                                                @csrf
                                                <label>Reason <textarea name="reason" maxlength="2000" required>{{ old('reason') }}</textarea></label>
                                                <button class="quote-button staff-auth-submit" type="submit">Archive expense</button>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @empty<tr><td colspan="5">No expenses recorded.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
                {{ $expenses->links() }}
            </section>
        </div>
    </section>
@endsection
