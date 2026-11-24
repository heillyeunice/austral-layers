<?php

namespace App\Http\Controllers;

use App\Exports\BusinessManagementReportExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessReportController extends Controller
{
    private const MONTHS = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    public function index(): View
    {
        return view('admin.business.reports.index', [
            'currentYear' => now()->year,
            'currentMonth' => now()->month,
            'months' => self::MONTHS,
            'noIndex' => true,
        ]);
    }

    public function export(Request $request, BusinessManagementReportExporter $exporter): Response
    {
        $validated = $request->validate([
            'period' => ['required', Rule::in(['monthly', 'annual'])],
            'year' => ['required', 'integer', 'between:2000,'.now()->year],
            'month' => ['nullable', 'required_if:period,monthly', 'integer', 'between:1,12'],
        ]);
        $year = (int) $validated['year'];
        $start = CarbonImmutable::create($year, (int) ($validated['month'] ?? 1), 1);
        $end = $validated['period'] === 'monthly' ? $start->endOfMonth() : $start->endOfYear();
        $periodLabel = $validated['period'] === 'monthly'
            ? $start->format('F Y')
            : (string) $year;
        $content = $exporter->make($start, $end, $periodLabel);

        abort_if($content === '', 500, 'The business report could not be generated.');

        $filename = $validated['period'] === 'monthly'
            ? 'austral-layers-business-monthly-'.$start->format('Y-m').'.xlsx'
            : 'austral-layers-business-annual-'.$year.'.xlsx';

        return response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }
}
