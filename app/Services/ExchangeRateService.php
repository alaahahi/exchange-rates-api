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
        if (config('exchange.live.enabled', true)) {
            // Lazy sync: refresh from provider when the sync TTL expires.
            app(SyncExchangeRatesService::class)->syncIfStale();
        }

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

    /**
     * @return array{source: string|null, source_label: string|null, live_enabled: bool, last_synced_at: string|null}
     */
    public function sourceMeta(): array
    {
        $usd = ExchangeRate::query()->where('currency_code', 'USD')->first();
        $providerKey = (string) config('exchange.live.provider', 'open_er_api');
        $provider = config('exchange.providers.'.$providerKey, []);
        $last = Cache::get(SyncExchangeRatesService::LAST_SYNC_CACHE_KEY);

        return [
            'source' => $usd?->source ?? ($provider['source_key'] ?? null),
            'source_label' => $provider['source_label'] ?? config('exchange.live.source_label'),
            'live_enabled' => (bool) config('exchange.live.enabled', true),
            'last_synced_at' => $last ? (is_string($last) ? $last : $last->toIso8601String()) : null,
        ];
    }
}
