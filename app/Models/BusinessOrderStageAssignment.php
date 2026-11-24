<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessOrderStageAssignment extends Model
{
    protected $fillable = [
        'order_id',
        'stage',
        'assigned_staff_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BusinessOrder::class, 'order_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }
}
