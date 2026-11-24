<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'new_order' => 'New Order',
        'pending_confirmation' => 'Pending Confirmation',
        'modeling' => 'Modeling / Design',
        'production' => 'Production',
        'quality_checking' => 'Quality Checking',
        'packaging' => 'Packaging',
        'ready_for_shipping' => 'Ready for Shipping',
        'shipped' => 'Shipped',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const ASSIGNABLE_STAGES = [
        'modeling',
        'production',
        'quality_checking',
        'packaging',
        'ready_for_shipping',
        'shipped',
    ];

    public const SALES_CHANNELS = [
        'tiktok' => 'TikTok',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'lazada' => 'Lazada',
        'shopee' => 'Shopee',
        'website' => 'Website / Quote inquiry',
        'other' => 'Other / Direct',
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'quote_inquiry_id',
        'item_name',
        'description',
        'specifications',
        'reference_files',
        'submission_token',
        'order_date',
        'deadline',
        'total_price',
        'sales_channel',
        'status',
        'assigned_staff_id',
        'internal_notes',
        'shipping_method',
        'tracking_number',
        'shipping_status',
        'deletion_reason',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'deadline' => 'date',
            'total_price' => 'decimal:2',
            'reference_files' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BusinessCustomer::class, 'customer_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(QuoteInquiry::class, 'quote_inquiry_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BusinessPayment::class, 'order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(BusinessOrderStatusHistory::class, 'order_id');
    }

    public function stageAssignments(): HasMany
    {
        return $this->hasMany(BusinessOrderStageAssignment::class, 'order_id');
    }

    public function stageAssignmentHistory(): HasMany
    {
        return $this->hasMany(BusinessOrderStageAssignmentHistory::class, 'order_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class, 'order_id');
    }
}
