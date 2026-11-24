<?php

namespace App\Http\Controllers;

use App\Models\PrinterPreset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrinterPresetController extends Controller
{
    public function index(): View
    {
        return view('admin.business.print-calculator.printers', [
            'printerPresets' => PrinterPreset::query()->orderBy('brand')->orderBy('model')->get(),
            'noIndex' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        PrinterPreset::query()->create($validated);

        return redirect()->route('admin.business.print-calculator.printers')
            ->with('business_saved', 'Printer preset added.');
    }

    public function update(Request $request, PrinterPreset $printerPreset): RedirectResponse
    {
        $validated = $request->validate($this->rules($printerPreset));
        $printerPreset->update($validated);

        return redirect()->route('admin.business.print-calculator.printers')
            ->with('business_saved', 'Printer preset updated.');
    }

    public function destroy(PrinterPreset $printerPreset): RedirectResponse
    {
        $printerPreset->delete();

        return redirect()->route('admin.business.print-calculator.printers')
            ->with('business_saved', 'Printer preset removed.');
    }

    private function rules(?PrinterPreset $printerPreset = null): array
    {
        $uniqueModel = Rule::unique('printer_presets', 'model')
            ->where('brand', request()->input('brand'));
        if ($printerPreset !== null) {
            $uniqueModel->ignore($printerPreset->id);
        }

        return [
            'brand' => ['required', 'string', 'max:120'],
            'model' => [
                'required',
                'string',
                'max:150',
                $uniqueModel,
            ],
            'power_watts' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'lifespan_hours' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];
    }
}
