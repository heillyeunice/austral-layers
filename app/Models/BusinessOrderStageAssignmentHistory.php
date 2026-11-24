<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessOrderStageAssignmentHistory extends Model
{
    protected $table = 'business_order_stage_assignment_history';

    protected $fillable = [
        'order_id',
        'stage',
        'assigned_staff_id',
        'assigned_staff_name',
        'changed_by',
        'changed_by_name',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BusinessOrder::class, 'order_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
