<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintQuotation extends Model
{
    protected $fillable = [
        'customer_name',
        'item_name',
        'filament_type',
        'weight_grams',
        'filament_cost_per_kg',
        'print_hours',
        'print_minutes',
        'electricity_rate',
        'printer_power_watts',
        'printer_price',
        'printer_lifespan_hours',
        'printer_preset_id',
        'printer_brand',
        'printer_model',
        'labor_rate',
        'labor_hours',
        'labor_minutes',
        'packaging_fee',
        'failure_rate',
        'profit_margin',
        'material_cost',
        'electricity_cost',
        'printer_wear_cost',
        'labor_cost',
        'subtotal',
        'failure_cost',
        'profit_amount',
        'final_price',
        'view_mode',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_grams' => 'decimal:2',
            'filament_cost_per_kg' => 'decimal:2',
            'electricity_rate' => 'decimal:4',
            'printer_power_watts' => 'decimal:2',
            'printer_price' => 'decimal:2',
            'printer_lifespan_hours' => 'decimal:2',
            'labor_rate' => 'decimal:2',
            'packaging_fee' => 'decimal:2',
            'failure_rate' => 'decimal:3',
            'profit_margin' => 'decimal:3',
            'material_cost' => 'decimal:2',
            'electricity_cost' => 'decimal:2',
            'printer_wear_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'failure_cost' => 'decimal:2',
            'profit_amount' => 'decimal:2',
            'final_price' => 'decimal:2',
        ];
    }

    public function printerPreset(): BelongsTo
    {
        return $this->belongsTo(PrinterPreset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
