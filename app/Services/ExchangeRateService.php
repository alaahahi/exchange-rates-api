<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    public const CACHE_KEY = 'exchange_rates.active';

    public function getActiveRates(?Tenant $tenant = null, ?string $location = null): Collection
    {
        $sync = app(SyncExchangeRatesService::class);
        $location = $sync->normalizeLocation($location);

        if (config('exchange.live.enabled', true)) {
            // Lazy sync: refresh from provider when the sync TTL expires.
            $sync->syncIfStale(false, $location);
        }

        $ttl = (int) config('exchange.cache_ttl', 120);

        $rates = Cache::remember($this->cacheKey($location), $ttl, function () use ($location) {
            return ExchangeRate::query()
                ->forLocation($location)
                ->active()
                ->ordered()
                ->get();
        });

        $needsResync = $rates->isEmpty();
        if (! $needsResync && (string) config('exchange.live.provider') === 'dinarlive') {
            $usd = $rates->firstWhere('currency_code', 'USD');
            // Old iraqsm rows were copied into every location — force real Dinarlive board.
            $needsResync = ! $usd || (string) $usd->source !== 'dinarlive.com';
            if (! $needsResync && $usd && $location !== $sync->defaultLocation()) {
                $maxAgeDays = max(1, (int) config('exchange.providers.dinarlive.max_age_days', 2));
                $needsResync = $usd->updated_at === null
                    || $usd->updated_at->lt(now()->subDays($maxAgeDays));
            }
        }

        if ($needsResync && config('exchange.live.enabled', true)) {
            try {
                $sync->sync(true, $location);
            } catch (\Throwable) {
                // keep whatever we have
            }
            Cache::forget($this->cacheKey($location));
            $rates = Cache::remember($this->cacheKey($location), $ttl, function () use ($location) {
                return ExchangeRate::query()
                    ->forLocation($location)
                    ->active()
                    ->ordered()
                    ->get();
            });
        }

        // If a non-default governorate is still empty/stale, serve Erbil instead.
        $defaultLocation = $sync->defaultLocation();
        if ($location !== $defaultLocation && $this->shouldFallbackToDefault($rates, $defaultLocation)) {
            $rates = Cache::remember($this->cacheKey($defaultLocation), $ttl, function () use ($defaultLocation) {
                return ExchangeRate::query()
                    ->forLocation($defaultLocation)
                    ->active()
                    ->ordered()
                    ->get();
            });
        }

        if (! $tenant || ! $tenant->usesCustomPrices()) {
            return $rates;
        }

        return $this->applyTenantOverrides($rates, $tenant);
    }

    private function shouldFallbackToDefault(Collection $rates, string $defaultLocation): bool
    {
        if ($rates->isEmpty()) {
            return true;
        }

        $usd = $rates->firstWhere('currency_code', 'USD');
        if (! $usd || (string) $usd->source !== 'dinarlive.com') {
            return true;
        }

        $maxAgeDays = max(1, (int) config('exchange.providers.dinarlive.max_age_days', 2));

        return $usd->updated_at === null
            || $usd->updated_at->lt(now()->subDays($maxAgeDays));
    }

    public function clearCache(?string $location = null): void
    {
        if ($location) {
            Cache::forget($this->cacheKey($location));

            return;
        }

        Cache::forget(self::CACHE_KEY);
        foreach (array_keys(config('exchange.providers.dinarlive.locations', [])) as $id) {
            Cache::forget($this->cacheKey((string) $id));
        }
    }

    /**
     * @param  Collection<int, ExchangeRate>  $rates
     * @return Collection<int, ExchangeRate>
     */
    private function applyTenantOverrides(Collection $rates, Tenant $tenant): Collection
    {
        $overrides = $tenant->rateOverrides()
            ->get()
            ->keyBy(fn ($row) => strtoupper((string) $row->currency_code));

        if ($overrides->isEmpty()) {
            return $rates;
        }

        return $rates->map(function (ExchangeRate $rate) use ($overrides) {
            $override = $overrides->get(strtoupper((string) $rate->currency_code));
            if (! $override) {
                return $rate;
            }

            $clone = $rate->replicate();
            $clone->setAttribute('id', $rate->id);
            $clone->exists = true;
            $clone->buy_rate = $override->buy_rate;
            $clone->sell_rate = $override->sell_rate;
            $clone->source = 'tenant';
            $clone->updated_at = $override->updated_at ?? $rate->updated_at;

            return $clone;
        });
    }

    /**
     * @return array{
     *   source: string|null,
     *   source_label: string|null,
     *   live_enabled: bool,
     *   last_synced_at: string|null,
     *   location: string,
     *   locations: list<array{id: string, label: string}>
     * }
     */
    public function sourceMeta(?string $location = null): array
    {
        $sync = app(SyncExchangeRatesService::class);
        $location = $sync->normalizeLocation($location);
        $usd = ExchangeRate::query()
            ->where('currency_code', 'USD')
            ->where('location', $location)
            ->first();
        $providerKey = (string) config('exchange.live.provider', 'dinarlive');
        $provider = config('exchange.providers.'.$providerKey, []);
        $last = Cache::get(SyncExchangeRatesService::LAST_SYNC_CACHE_KEY.'.'.$location)
            ?? Cache::get(SyncExchangeRatesService::LAST_SYNC_CACHE_KEY);

        $labels = config('exchange.providers.dinarlive.locations', []);
        $sourceLabel = $provider['source_label'] ?? config('exchange.live.source_label');
        if (isset($labels[$location])) {
            $sourceLabel = trim((string) ($provider['source_label'] ?? 'Dinarlive')).' — '.$labels[$location];
        }

        return [
            'source' => $usd?->source ?? ($provider['source_key'] ?? null),
            'source_label' => $sourceLabel,
            'live_enabled' => (bool) config('exchange.live.enabled', true),
            'cache_ttl' => (int) config('exchange.cache_ttl', 120),
            'last_synced_at' => $last ? (is_string($last) ? $last : $last->toIso8601String()) : null,
            'location' => $location,
            'locations' => $sync->locationOptions(),
        ];
    }

    private function cacheKey(string $location): string
    {
        return self::CACHE_KEY.'.'.$location;
    }
}
