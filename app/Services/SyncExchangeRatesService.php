<?php

namespace App\Services;

use App\Contracts\ExchangeRateProvider;
use App\Models\ExchangeRate;
use App\Services\RateProviders\DinarLiveProvider;
use App\Services\RateProviders\IraqSmProvider;
use App\Services\RateProviders\OpenErApiProvider;
use App\Services\RateProviders\QamarAlFajrProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SyncExchangeRatesService
{
    public const LAST_SYNC_CACHE_KEY = 'exchange_rates.last_sync_at';

    public const RAW_CACHE_KEY = 'exchange_rates.live_raw';

    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function provider(): ExchangeRateProvider
    {
        $name = (string) config('exchange.live.provider', 'dinarlive');

        return match ($name) {
            'dinarlive' => app(DinarLiveProvider::class),
            'iraqsm' => app(IraqSmProvider::class),
            'qamar' => app(QamarAlFajrProvider::class),
            'open_er_api' => app(OpenErApiProvider::class),
            default => throw new InvalidArgumentException("Unknown exchange rate provider [{$name}]."),
        };
    }

    public function defaultLocation(): string
    {
        $provider = $this->provider();
        if ($provider instanceof DinarLiveProvider) {
            return $provider->defaultLocation();
        }

        return (string) config('exchange.live.location', 'erbil');
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function locationOptions(): array
    {
        $labels = config('exchange.providers.dinarlive.locations', []);
        $options = [];
        foreach ($labels as $id => $label) {
            $options[] = [
                'id' => (string) $id,
                'label' => (string) $label,
            ];
        }

        return $options;
    }

    public function syncIfStale(bool $force = false, ?string $location = null): bool
    {
        if (! config('exchange.live.enabled', true)) {
            return false;
        }

        $location = $this->normalizeLocation($location);
        $ttl = (int) config('exchange.live.sync_ttl', 120);
        $lastAt = $this->asCarbon(Cache::get($this->lastSyncKey($location)));

        if (! $force && $lastAt !== null && $lastAt->diffInSeconds(now()) < $ttl) {
            return false;
        }

        $lock = Cache::lock('exchange_rates.sync_lock.'.$location, 45);
        if (! $lock->get()) {
            return false;
        }

        try {
            $lastAgain = $this->asCarbon(Cache::get($this->lastSyncKey($location)));
            if (! $force && $lastAgain !== null && $lastAgain->diffInSeconds(now()) < $ttl) {
                return false;
            }

            $this->sync($force, $location);

            return true;
        } catch (Throwable $e) {
            Log::warning('Exchange rate sync failed.', [
                'location' => $location,
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @return array{source: string, source_label: string, updated: int, fetched_at: string, location: string}
     */
    public function sync(bool $forceRefresh = false, ?string $location = null): array
    {
        $provider = $this->provider();
        $ttl = (int) config('exchange.live.sync_ttl', 120);

        // Only Dinarlive has true per-governorate boards. Other providers are
        // single-market and must not be copied into every location bucket.
        if ($provider instanceof DinarLiveProvider) {
            $location = $provider->resolveLocation($location);
        } else {
            $location = $provider instanceof IraqSmProvider
                ? 'baghdad'
                : $this->normalizeLocation($location);
        }

        $cacheKey = $this->rawCacheKey($location);
        $payload = $forceRefresh ? null : Cache::get($cacheKey);

        if (! is_array($payload)) {
            $payload = $provider instanceof DinarLiveProvider
                ? $provider->fetchForLocation($location)
                : $provider->fetchMidRatesInIqd();

            if (! isset($payload['location'])) {
                $payload['location'] = $location;
            }

            Cache::put($cacheKey, $payload, $ttl);
        }

        $source = (string) ($payload['source'] ?? 'live');
        $requestedLocation = $location;
        $location = (string) ($payload['location'] ?? $location);
        $updated = 0;

        if (isset($payload['board_rates']) && is_array($payload['board_rates'])) {
            $updated = $this->persistBoardRates($payload['board_rates'], $source, $location);
        } else {
            $updated = $this->persistMidRates($payload['rates'] ?? [], $source, $location);
        }

        // If we fell back to Erbil, retire the stale governorate board so the UI
        // cannot keep serving an old Baghdad quote.
        $fallbackFrom = $payload['fallback_from'] ?? null;
        if (is_string($fallbackFrom) && $fallbackFrom !== '' && $fallbackFrom !== $location) {
            ExchangeRate::query()
                ->where('location', $fallbackFrom)
                ->update(['is_active' => false]);
            $this->exchangeRateService->clearCache($fallbackFrom);
        } elseif ($requestedLocation !== $location) {
            ExchangeRate::query()
                ->where('location', $requestedLocation)
                ->update(['is_active' => false]);
            $this->exchangeRateService->clearCache($requestedLocation);
        }

        $this->exchangeRateService->clearCache($location);
        Cache::put($this->lastSyncKey($location), now(), $ttl * 2);
        Cache::put(self::LAST_SYNC_CACHE_KEY, now(), $ttl * 2);

        return [
            'source' => $source,
            'source_label' => (string) ($payload['source_label'] ?? config('exchange.live.source_label')),
            'updated' => $updated,
            'fetched_at' => $payload['fetched_at'] ?? now()->toIso8601String(),
            'location' => $location,
        ];
    }

    /**
     * Best-effort sync of every configured governorate (skips failures).
     *
     * @return array{synced: list<string>, failed: array<string, string>}
     */
    public function syncAllLocations(bool $forceRefresh = false): array
    {
        $synced = [];
        $failed = [];
        $provider = $this->provider();

        if (! $provider instanceof DinarLiveProvider) {
            $result = $this->sync($forceRefresh, null);
            $synced[] = $result['location'];

            return compact('synced', 'failed');
        }

        foreach ($this->locationOptions() as $option) {
            $id = $option['id'];
            try {
                $this->sync($forceRefresh, $id);
                $synced[] = $id;
                // Dinarlive allows ~10 req/min; each location uses several calls.
                usleep(700000);
            } catch (Throwable $e) {
                $failed[$id] = $e->getMessage();
                Log::warning('Governorate sync failed.', [
                    'location' => $id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return compact('synced', 'failed');
    }

    /**
     * @param  array<string, array{buy: float|int|string, sell: float|int|string}>  $boardRates
     */
    private function persistBoardRates(array $boardRates, string $source, string $location): int
    {
        $priority = config('exchange.live.priority', []);
        $names = config('exchange.currency_names', []);
        $updated = 0;
        $activeCodes = [];

        DB::transaction(function () use ($boardRates, $source, $location, $priority, $names, &$updated, &$activeCodes) {
            ExchangeRate::query()
                ->where('location', $location)
                ->where('currency_code', 'IQD')
                ->delete();

            foreach ($boardRates as $code => $row) {
                $code = strtoupper((string) $code);
                if ($code === 'IQD') {
                    continue;
                }

                $buy = (float) ($row['buy'] ?? 0);
                $sell = (float) ($row['sell'] ?? 0);
                if ($buy <= 0 || $sell <= 0) {
                    continue;
                }

                $activeCodes[] = $code;
                $existing = ExchangeRate::query()
                    ->where('currency_code', $code)
                    ->where('location', $location)
                    ->first();
                $previousMid = $existing
                    ? (((float) $existing->buy_rate + (float) $existing->sell_rate) / 2)
                    : null;

                $change = null;
                if ($previousMid !== null && $previousMid > 0) {
                    $newMid = ($buy + $sell) / 2;
                    $change = round((($newMid - $previousMid) / $previousMid) * 100, 4);
                }

                ExchangeRate::query()->updateOrCreate(
                    [
                        'currency_code' => $code,
                        'location' => $location,
                    ],
                    [
                        'currency_name' => $names[$code] ?? $code,
                        'buy_rate' => $this->roundBoard($buy),
                        'sell_rate' => $this->roundBoard($sell),
                        'change_percentage' => $change,
                        'is_active' => true,
                        'sort_order' => (int) ($priority[$code] ?? (1000 + ord($code[0]))),
                        'source' => $source,
                    ],
                );

                $updated++;
            }

            if ($activeCodes !== []) {
                ExchangeRate::query()
                    ->where('location', $location)
                    ->whereNotIn('currency_code', $activeCodes)
                    ->update(['is_active' => false]);
            }
        });

        return $updated;
    }

    /**
     * @param  array<string, float|int|string>  $rates
     */
    private function persistMidRates(array $rates, string $source, string $location): int
    {
        $quoteUnit = max(1, (int) config('exchange.live.quote_unit', 100));
        $buySpread = max(0, (float) config('exchange.live.buy_spread_percent', 0.35)) / 100;
        $sellSpread = max(0, (float) config('exchange.live.sell_spread_percent', 0.35)) / 100;
        $priority = config('exchange.live.priority', []);
        $names = config('exchange.currency_names', []);
        $updated = 0;
        $activeCodes = [];

        DB::transaction(function () use (
            $rates,
            $quoteUnit,
            $buySpread,
            $sellSpread,
            $priority,
            $names,
            $source,
            $location,
            &$updated,
            &$activeCodes,
        ) {
            ExchangeRate::query()
                ->where('location', $location)
                ->where('currency_code', 'IQD')
                ->delete();

            foreach ($rates as $code => $midPerUnit) {
                $code = strtoupper((string) $code);
                if ($code === 'IQD') {
                    continue;
                }

                $activeCodes[] = $code;
                $existing = ExchangeRate::query()
                    ->where('currency_code', $code)
                    ->where('location', $location)
                    ->first();
                $previousMid = $existing
                    ? (((float) $existing->buy_rate + (float) $existing->sell_rate) / 2)
                    : null;

                $boardMid = (float) $midPerUnit * $quoteUnit;
                $buy = $this->roundBoard($boardMid * (1 - $buySpread));
                $sell = $this->roundBoard($boardMid * (1 + $sellSpread));
                if ($sell < $buy) {
                    $sell = $buy;
                }

                $change = null;
                if ($previousMid !== null && $previousMid > 0) {
                    $newMid = ($buy + $sell) / 2;
                    $change = round((($newMid - $previousMid) / $previousMid) * 100, 4);
                }

                ExchangeRate::query()->updateOrCreate(
                    [
                        'currency_code' => $code,
                        'location' => $location,
                    ],
                    [
                        'currency_name' => $names[$code] ?? $code,
                        'buy_rate' => $buy,
                        'sell_rate' => $sell,
                        'change_percentage' => $change,
                        'is_active' => true,
                        'sort_order' => (int) ($priority[$code] ?? (1000 + ord($code[0]))),
                        'source' => $source,
                    ],
                );

                $updated++;
            }

            if ($activeCodes !== []) {
                ExchangeRate::query()
                    ->where('location', $location)
                    ->whereNotIn('currency_code', $activeCodes)
                    ->update(['is_active' => false]);
            }
        });

        return $updated;
    }

    public function normalizeLocation(?string $location): string
    {
        $provider = $this->provider();
        if ($provider instanceof DinarLiveProvider) {
            return $provider->resolveLocation($location);
        }

        $candidate = strtolower(trim((string) ($location ?: config('exchange.live.location', 'erbil'))));

        return $candidate !== '' ? $candidate : 'erbil';
    }

    private function rawCacheKey(string $location): string
    {
        return self::RAW_CACHE_KEY.'.'.$location;
    }

    private function lastSyncKey(string $location): string
    {
        return self::LAST_SYNC_CACHE_KEY.'.'.$location;
    }

    private function roundBoard(float $value): float
    {
        if ($value >= 100) {
            return round($value, 0);
        }

        if ($value >= 1) {
            return round($value, 2);
        }

        return round($value, 4);
    }

    private function asCarbon(mixed $value): ?\Carbon\CarbonInterface
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return \Carbon\CarbonImmutable::parse($value);
        }

        return null;
    }
}
