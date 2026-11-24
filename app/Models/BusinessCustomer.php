<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCustomer extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'shipping_address',
        'social_media_link',
        'notes',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(BusinessOrder::class, 'customer_id');
    }
}
