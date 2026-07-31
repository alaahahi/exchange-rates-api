<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    public const CACHE_KEY = 'exchange_rates.active';

    public function getActiveRates(): Collection
    {
        $ttl = (int) config('exchange.cache_ttl', 60);

        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            return ExchangeRate::query()
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
