<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrinterPreset extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'power_watts',
        'price',
        'lifespan_hours',
    ];

    protected function casts(): array
    {
        return [
            'power_watts' => 'decimal:2',
            'price' => 'decimal:2',
            'lifespan_hours' => 'decimal:2',
        ];
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(PrintQuotation::class);
    }
}
