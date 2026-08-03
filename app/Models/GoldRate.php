<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'buy_rate',
        'sell_rate',
        'change_percentage',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'buy_rate' => 'decimal:4',
            'sell_rate' => 'decimal:4',
            'change_percentage' => 'decimal:4',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
