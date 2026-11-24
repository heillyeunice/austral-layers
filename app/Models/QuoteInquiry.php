<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteInquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'social_media_link',
        'service',
        'quantity',
        'message',
        'inspiration_files',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'inspiration_files' => 'array',
        ];
    }

    public function businessOrder(): HasOne
    {
        return $this->hasOne(BusinessOrder::class, 'quote_inquiry_id')->withTrashed();
    }
}
