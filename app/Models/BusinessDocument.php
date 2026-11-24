<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDocument extends Model
{
    protected $fillable = [
        'order_id',
        'document_number',
        'invoice_number',
        'invoice_document_id',
        'payment_id',
        'type',
        'status',
        'issued_on',
        'due_on',
        'snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'due_on' => 'date',
            'snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BusinessOrder::class, 'order_id');
    }

    public function invoiceDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invoice_document_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(self::class, 'invoice_document_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(BusinessPayment::class, 'payment_id');
    }
}
