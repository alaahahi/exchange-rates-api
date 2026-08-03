<?php

namespace App\Services;

use App\Models\GoldRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GoldRateService
{
    public const CACHE_KEY = 'gold_rates.active';

    public function getActiveRates(): Collection
    {
        $ttl = (int) config('exchange.gold_cache_ttl', config('exchange.cache_ttl', 60));

        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            return GoldRate::query()
                ->active()
                ->ordered()
                ->get();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
