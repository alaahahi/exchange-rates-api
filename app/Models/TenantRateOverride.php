<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRateOverride extends Model
{
    protected $fillable = [
        'tenant_id',
        'currency_code',
        'buy_rate',
        'sell_rate',
    ];

    protected function casts(): array
    {
        return [
            'buy_rate' => 'decimal:4',
            'sell_rate' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
