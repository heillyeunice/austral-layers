<?php

namespace App\Http\Controllers;

use App\Models\PrinterPreset;
use App\Models\PrintQuotation;
use App\Services\PrintQuoteCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PrintCalculatorController extends Controller
{
    private const FILAMENT_TYPES = ['PLA', 'PETG', 'ABS', 'ASA', 'TPU', 'Nylon', 'Resin', 'Other'];

    public function index(): View
    {
        return view('admin.business.print-calculator.index', [
            'printerPresets' => PrinterPreset::query()->orderBy('brand')->orderBy('model')->get(),
            'recentQuotations' => PrintQuotation::query()->latest()->limit(10)->get(),
            'filamentTypes' => self::FILAMENT_TYPES,
            'failureRate' => PrintQuoteCalculator::FAILURE_RATE_PERCENT,
            'markup' => PrintQuoteCalculator::MARKUP_PERCENT,
            'noIndex' => true,
        ]);
    }

    public function generate(Request $request, PrintQuoteCalculator $calculator): Response
    {
        [$validated, $costs] = $this->validatedCosting($request, $calculator);
        $quotation = PrintQuotation::query()->create([
            ...$validated,
            ...$costs,
            'created_by' => $request->user()->id,
        ]);

        return $this->pdfResponse($quotation);
    }

    public function prepareOrder(Request $request, PrintQuoteCalculator $calculator): RedirectResponse
    {
        [$validated, $costs] = $this->validatedCosting($request, $calculator);
        $totalPrice = round($costs['final_price'], 2);

        if ($totalPrice < 50) {
            throw ValidationException::withMessages([
                'item_name' => 'The calculated price is below the ₱50 minimum for a business order.',
            ]);
        }

        $printerName = trim(($validated['printer_brand'] ?? '').' '.($validated['printer_model'] ?? ''));
        $specifications = sprintf(
            "Filament: %s (%s g)\nEstimated print time: %d h %d min\nPrinter: %s\nPackaging: included.",
            $validated['filament_type'],
            number_format((float) $validated['weight_grams'], 2),
            $validated['print_hours'],
            $validated['print_minutes'],
            $printerName !== '' ? $printerName : 'Manual printer entry',
        );

        return redirect()->route('admin.business.orders.create')->withInput([
            'customer_name' => $validated['customer_name'] ?? '',
            'item_name' => $validated['item_name'],
            'total_price' => number_format($totalPrice, 2, '.', ''),
            'order_date' => today()->toDateString(),
            'status' => 'new_order',
            'description' => '3D print order priced using the Business Management cost calculator.',
            'specifications' => $specifications,
        ])->with('business_saved', 'Calculator costing added to the order form. Review the details and complete the order.');
    }

    private function validatedCosting(Request $request, PrintQuoteCalculator $calculator): array
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:150'],
            'item_name' => ['required', 'string', 'max:180'],
            'filament_type' => ['required', Rule::in(self::FILAMENT_TYPES)],
            'weight_grams' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'filament_cost_per_kg' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'print_hours' => ['required', 'integer', 'min:0', 'max:9999'],
            'print_minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'electricity_rate' => ['required', 'numeric', 'min:0', 'max:99999999.9999'],
            'printer_power_watts' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'printer_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'printer_lifespan_hours' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'printer_preset_id' => ['nullable', 'integer', 'exists:printer_presets,id'],
            'printer_brand' => ['nullable', 'string', 'max:120'],
            'printer_model' => ['nullable', 'string', 'max:150'],
            'labor_rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'labor_hours' => ['required', 'integer', 'min:0', 'max:9999'],
            'labor_minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'packaging_fee' => ['required', Rule::in(['0', '30', '50'])],
            'view_mode' => ['required', Rule::in(['internal', 'client'])],
        ]);

        if ((int) $validated['print_hours'] === 0 && (int) $validated['print_minutes'] === 0) {
            throw ValidationException::withMessages([
                'print_hours' => 'Enter a print duration greater than zero.',
            ]);
        }

        $preset = isset($validated['printer_preset_id'])
            ? PrinterPreset::query()->findOrFail($validated['printer_preset_id'])
            : null;
        $validated['printer_brand'] = $preset?->brand;
        $validated['printer_model'] = $preset?->model;

        $costs = $calculator->calculate($validated);
        $validated['failure_rate'] = PrintQuoteCalculator::FAILURE_RATE_PERCENT;
        $validated['profit_margin'] = PrintQuoteCalculator::MARKUP_PERCENT;

        foreach ($costs as $cost) {
            if (! is_finite($cost) || $cost > 9999999999.99) {
                throw ValidationException::withMessages([
                    'weight_grams' => 'These inputs produce a price beyond the supported range.',
                ]);
            }
        }

        return [$validated, $costs];
    }

    public function download(PrintQuotation $printQuotation): Response
    {
        return $this->pdfResponse($printQuotation);
    }

    private function pdfResponse(PrintQuotation $quotation): Response
    {
        $pdf = Pdf::loadView('admin.business.print-calculator.quotation', [
            'quotation' => $quotation,
            'noIndex' => true,
        ])->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="3d-print-quotation-'.$quotation->id.'.pdf"',
        ]);
    }
}
