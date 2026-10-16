<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    public const PRICE_PANEL = 'panel';

    public const PRICE_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'price_mode',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function rateOverrides(): HasMany
    {
        return $this->hasMany(TenantRateOverride::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function usesCustomPrices(): bool
    {
        return $this->price_mode === self::PRICE_CUSTOM && ! $this->is_primary;
    }

    public function logoUrl(): string
    {
        if ($this->logo_path) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return rtrim((string) config('app.url'), '/').'/logo.png';
    }

    /**
     * @return array{name: string, slug: string|null, logo_url: string, is_primary: bool, show_ads: bool, price_mode: string}
     */
    public function toSitePayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->is_primary ? null : $this->slug,
            'logo_url' => $this->logoUrl(),
            'is_primary' => (bool) $this->is_primary,
            'show_ads' => (bool) $this->is_primary,
            'price_mode' => $this->price_mode,
        ];
    }
}
