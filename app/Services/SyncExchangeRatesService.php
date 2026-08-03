<?php

namespace App\Services;

use App\Contracts\ExchangeRateProvider;
use App\Models\ExchangeRate;
use App\Services\RateProviders\OpenErApiProvider;
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
        $name = (string) config('exchange.live.provider', 'open_er_api');

        return match ($name) {
            'open_er_api' => app(OpenErApiProvider::class),
            default => throw new InvalidArgumentException("Unknown exchange rate provider [{$name}]."),
        };
    }

    public function syncIfStale(bool $force = false): bool
    {
        if (! config('exchange.live.enabled', true)) {
            return false;
        }

        $ttl = (int) config('exchange.live.sync_ttl', 300);
        $lastAt = $this->asCarbon(Cache::get(self::LAST_SYNC_CACHE_KEY));

        if (! $force && $lastAt !== null && $lastAt->diffInSeconds(now()) < $ttl) {
            return false;
        }

        $lock = Cache::lock('exchange_rates.sync_lock', 30);
        if (! $lock->get()) {
            return false;
        }

        try {
            $lastAgain = $this->asCarbon(Cache::get(self::LAST_SYNC_CACHE_KEY));
            if (! $force && $lastAgain !== null && $lastAgain->diffInSeconds(now()) < $ttl) {
                return false;
            }

            $this->sync($force);

            return true;
        } catch (Throwable $e) {
            Log::warning('Exchange rate sync failed.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @return array{source: string, source_label: string, updated: int, fetched_at: string}
     */
    public function sync(bool $forceRefresh = false): array
    {
        $ttl = (int) config('exchange.live.sync_ttl', 300);

        $payload = $forceRefresh ? null : Cache::get(self::RAW_CACHE_KEY);

        if (! is_array($payload)) {
            $payload = $this->provider()->fetchMidRatesInIqd();
            Cache::put(self::RAW_CACHE_KEY, $payload, $ttl);
        }

        $quoteUnit = max(1, (int) config('exchange.live.quote_unit', 100));
        $buySpread = max(0, (float) config('exchange.live.buy_spread_percent', 0.35)) / 100;
        $sellSpread = max(0, (float) config('exchange.live.sell_spread_percent', 0.35)) / 100;
        $priority = config('exchange.live.priority', []);
        $names = config('exchange.currency_names', []);
        $source = (string) ($payload['source'] ?? 'live');
        $updated = 0;
        $activeCodes = [];

        DB::transaction(function () use (
            $payload,
            $quoteUnit,
            $buySpread,
            $sellSpread,
            $priority,
            $names,
            $source,
            &$updated,
            &$activeCodes,
        ) {
            // Never keep IQD as a listed currency.
            ExchangeRate::query()->where('currency_code', 'IQD')->delete();

            foreach ($payload['rates'] as $code => $midPerUnit) {
                $code = strtoupper((string) $code);
                if ($code === 'IQD') {
                    continue;
                }

                $activeCodes[] = $code;
                $existing = ExchangeRate::query()->where('currency_code', $code)->first();
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
                    ['currency_code' => $code],
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
                    ->whereNotIn('currency_code', $activeCodes)
                    ->update(['is_active' => false]);
            }
        });

        $this->exchangeRateService->clearCache();
        Cache::put(self::LAST_SYNC_CACHE_KEY, now(), $ttl * 2);

        return [
            'source' => $source,
            'source_label' => (string) ($payload['source_label'] ?? config('exchange.live.source_label')),
            'updated' => $updated,
            'fetched_at' => $payload['fetched_at'] ?? now()->toIso8601String(),
        ];
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
