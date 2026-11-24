<?php

namespace App\Services;

class PrintQuoteCalculator
{
    public const FAILURE_RATE_PERCENT = 30;

    public const MARKUP_PERCENT = 200;

    public function calculate(array $inputs): array
    {
        $printHours = (int) $inputs['print_hours'] + ((int) $inputs['print_minutes'] / 60);
        $laborHours = (int) $inputs['labor_hours'] + ((int) $inputs['labor_minutes'] / 60);

        $materialCost = ((float) $inputs['weight_grams'] / 1000) * (float) $inputs['filament_cost_per_kg'];
        $electricityCost = ((float) $inputs['printer_power_watts'] / 1000) * $printHours * (float) $inputs['electricity_rate'];
        $printerWearCost = ((float) $inputs['printer_price'] / (float) $inputs['printer_lifespan_hours']) * $printHours;
        $laborCost = (float) $inputs['labor_rate'] * $laborHours;
        $subtotal = $materialCost + $electricityCost + $printerWearCost + $laborCost + (float) $inputs['packaging_fee'];
        $failureCost = $subtotal * (self::FAILURE_RATE_PERCENT / 100);
        $priceBeforeProfit = $subtotal + $failureCost;
        $profitAmount = $priceBeforeProfit * (self::MARKUP_PERCENT / 100);

        return [
            'material_cost' => $materialCost,
            'electricity_cost' => $electricityCost,
            'printer_wear_cost' => $printerWearCost,
            'labor_cost' => $laborCost,
            'subtotal' => $subtotal,
            'failure_cost' => $failureCost,
            'profit_amount' => $profitAmount,
            'final_price' => $priceBeforeProfit + $profitAmount,
        ];
    }
}
