@extends('layouts.app', [
    'pageTitle' => 'Business reports — Austral Layers',
    'pageDescription' => 'Export monthly or annual business management reports.',
    'noIndex' => true,
])

@section('content')
    <section class="staff-dashboard staff-content-page business-report-page">
        @include('admin.business.nav')
        <div class="staff-content-heading">
            <div>
                <p class="eyebrow"><span></span> Business management</p>
                <h1>Reports &amp; exports</h1>
                <p>Download a formatted Excel workbook that you can also open in Google Sheets.</p>
            </div>
        </div>

        <section class="business-report-includes" aria-labelledby="report-includes-title">
            <h2 id="report-includes-title">What’s in the workbook</h2>
            <p>Summary, orders, payments, expenses, customers, and invoices &amp; receipts.</p>
            <p>Archived orders, payments for archived orders, and archived expenses are excluded. Orders use order date, payments use payment date, expenses use expense date, and documents use issue date. Customers are included if created or ordered during the period.</p>
        </section>

        <div class="business-report-periods">
            <section class="business-report-option" aria-labelledby="monthly-report-title">
                <p class="eyebrow"><span></span> Monthly</p>
                <h2 id="monthly-report-title">Choose a month</h2>
                <p>One workbook with the six report sheets for the selected month.</p>
                <form class="business-report-form" method="get" action="{{ route('admin.business.reports.export') }}">
                    <input type="hidden" name="period" value="monthly">
                    <label>
                        <span>Month</span>
                        <select name="month" required>
                            @foreach ($months as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" @selected($currentMonth === $monthNumber)>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Year</span>
                        <input type="number" name="year" min="2000" max="{{ $currentYear }}" value="{{ $currentYear }}" required>
                    </label>
                    <button class="quote-button" type="submit">Download monthly workbook <span>↓</span></button>
                </form>
            </section>

            <section class="business-report-option" aria-labelledby="annual-report-title">
                <p class="eyebrow"><span></span> Annual</p>
                <h2 id="annual-report-title">Choose a year</h2>
                <p>Review the full calendar year across the same report sheets.</p>
                <form class="business-report-form business-report-form-annual" method="get" action="{{ route('admin.business.reports.export') }}">
                    <input type="hidden" name="period" value="annual">
                    <label>
                        <span>Year</span>
                        <input type="number" name="year" min="2000" max="{{ $currentYear }}" value="{{ $currentYear }}" required>
                    </label>
                    <button class="quote-button" type="submit">Download annual workbook <span>↓</span></button>
                </form>
            </section>
        </div>
    </section>
@endsection
