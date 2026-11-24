<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'images',
        'order_links',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'order_links' => 'array',
        ];
    }
}
