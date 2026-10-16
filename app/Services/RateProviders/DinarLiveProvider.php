<?php

namespace App\Services\RateProviders;

use App\Contracts\ExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Per-governorate cash board from Dinarlive (free, no API key).
 * Values are already quoted per 100 units for USD/EUR/GBP/TRY.
 */
class DinarLiveProvider implements ExchangeRateProvider
{
    /** @var array<int, string> */
    private const ITEM_IDS = [
        5 => 'USD',
        6 => 'EUR',
        7 => 'GBP',
        8 => 'TRY',
    ];

    public function fetchMidRatesInIqd(): array
    {
        $location = $this->resolveLocation(null);

        return $this->fetchForLocation($location);
    }

    /**
     * @return array{
     *   source: string,
     *   source_label: string,
     *   fetched_at: string,
     *   location: string,
     *   rates: array<string, float>,
     *   board_rates: array<string, array{buy: float, sell: float}>
     * }
     */
    public function fetchForLocation(?string $location = null): array
    {
        $config = config('exchange.providers.dinarlive', []);
        $location = $this->resolveLocation($location);
        $timeout = (int) config('exchange.live.http_timeout', 12);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://dinarlive.com/api/v2/get-price'), '?');
        $crossUrl = (string) ($config['cross_rates_url'] ?? config('exchange.providers.open_er_api.base_url'));
        $buySpread = max(0, (float) config('exchange.live.buy_spread_percent', 0)) / 100;
        $sellSpread = max(0, (float) config('exchange.live.sell_spread_percent', 0)) / 100;

        $boardRates = [];
        $latestAt = null;

        foreach (self::ITEM_IDS as $id => $code) {
            try {
                $payload = $this->fetchItem($baseUrl, $id, $location, $timeout);
            } catch (\Throwable $e) {
                // Non-Erbil boards often only publish USD; skip missing pairs.
                continue;
            }

            $value = (float) ($payload['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }

            $buy = $value * (1 - $buySpread);
            $sell = $value * (1 + $sellSpread);
            if ($sell < $buy) {
                $sell = $buy;
            }

            $boardRates[$code] = [
                'buy' => $buy,
                'sell' => $sell,
            ];

            $created = $payload['created_at'] ?? null;
            if (is_string($created) && ($latestAt === null || $created > $latestAt)) {
                $latestAt = $created;
            }

            usleep(150000);
        }

        if ($boardRates === [] || ! isset($boardRates['USD'])) {
            throw new RuntimeException("Dinarlive returned no USD rate for [{$location}].");
        }

        $usdMid = (((float) $boardRates['USD']['buy'] + (float) $boardRates['USD']['sell']) / 2);
        $quoteUnit = max(1, (int) config('exchange.live.quote_unit', 100));
        $usdMidPerUnit = $usdMid / $quoteUnit;
        $buyFactor = $usdMid > 0 ? ((float) $boardRates['USD']['buy'] / $usdMid) : 1;
        $sellFactor = $usdMid > 0 ? ((float) $boardRates['USD']['sell'] / $usdMid) : 1;

        try {
            $cross = $this->fetchCrossRates($crossUrl, $timeout);
            foreach (array_keys(config('exchange.live.priority', [])) as $code) {
                $code = strtoupper((string) $code);
                if ($code === 'USD' || $code === 'IQD' || isset($boardRates[$code])) {
                    continue;
                }

                $unitsPerUsd = (float) ($cross[$code] ?? 0);
                if ($unitsPerUsd <= 0) {
                    continue;
                }

                $boardMid = ($usdMidPerUnit / $unitsPerUsd) * $quoteUnit;
                $boardRates[$code] = [
                    'buy' => $boardMid * $buyFactor,
                    'sell' => $boardMid * $sellFactor,
                ];
            }
        } catch (\Throwable) {
            // Keep Dinarlive board currencies even if cross-rate expand fails.
        }

        $mids = [];
        foreach ($boardRates as $code => $row) {
            $mids[$code] = (((float) $row['buy'] + (float) $row['sell']) / 2) / $quoteUnit;
        }

        $labels = config('exchange.providers.dinarlive.locations', []);
        $label = (string) ($labels[$location] ?? $location);

        return [
            'source' => (string) ($config['source_key'] ?? 'dinarlive.com'),
            'source_label' => (string) ($config['source_label'] ?? 'Dinarlive')." — {$label}",
            'fetched_at' => now()->toIso8601String(),
            'provider_updated_at' => $latestAt,
            'location' => $location,
            'rates' => $mids,
            'board_rates' => $boardRates,
        ];
    }

    /**
     * @return list<string>
     */
    public function availableLocations(): array
    {
        return array_keys(config('exchange.providers.dinarlive.locations', [
            'erbil' => 'أربيل',
        ]));
    }

    public function defaultLocation(): string
    {
        return $this->resolveLocation(null);
    }

    public function resolveLocation(?string $location): string
    {
        $locations = $this->availableLocations();
        $fallback = (string) config('exchange.providers.dinarlive.default_location', 'erbil');
        $candidate = strtolower(trim((string) ($location ?: config('exchange.live.location', $fallback))));

        if ($candidate === '' || ! in_array($candidate, $locations, true)) {
            return in_array($fallback, $locations, true) ? $fallback : ($locations[0] ?? 'erbil');
        }

        return $candidate;
    }

    /**
     * @return array{value?: float|int|string, created_at?: string}
     */
    private function fetchItem(string $baseUrl, int $id, string $location, int $timeout): array
    {
        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'dinar-now-exchange-rates/1.0'])
            ->get($baseUrl, [
                'id' => $id,
                'location' => $location,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Dinarlive HTTP {$response->status()} for id={$id} location={$location}");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException("Dinarlive invalid JSON for id={$id} location={$location}");
        }

        return $payload;
    }

    /**
     * @return array<string, float>
     */
    private function fetchCrossRates(string $url, int $timeout): array
    {
        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'dinar-now-exchange-rates/1.0'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Cross-rate API returned HTTP '.$response->status());
        }

        $payload = $response->json();
        if (($payload['result'] ?? null) !== 'success' || ! is_array($payload['rates'] ?? null)) {
            throw new RuntimeException('Cross-rate API returned an invalid payload.');
        }

        $rates = [];
        foreach ($payload['rates'] as $code => $value) {
            $rates[strtoupper((string) $code)] = (float) $value;
        }

        return $rates;
    }
}
