<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioProject extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'status',
        'keywords',
        'description',
        'work',
        'images',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'images' => 'array',
        ];
    }
}
