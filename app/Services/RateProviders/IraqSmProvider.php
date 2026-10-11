<?php

namespace App\Services\RateProviders;

use App\Contracts\ExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Baghdad parallel-market USD from IQWealth (iraqsm.com), with other
 * currencies scaled via open.er-api cross rates against that USD board.
 */
class IraqSmProvider implements ExchangeRateProvider
{
    public function fetchMidRatesInIqd(): array
    {
        $config = config('exchange.providers.iraqsm', []);
        $timeout = (int) config('exchange.live.http_timeout', 12);
        $quoteUnit = max(1, (int) config('exchange.live.quote_unit', 100));
        $fxUrl = (string) ($config['fx_url'] ?? 'https://iraqsm.com/data/fx.json');
        $crossUrl = (string) ($config['cross_rates_url'] ?? config('exchange.providers.open_er_api.base_url'));

        $fx = $this->fetchFxJson($fxUrl, $timeout);
        $buyPerUsd = (float) data_get($fx, 'parallel.buy', 0);
        $sellPerUsd = (float) data_get($fx, 'parallel.sell', 0);

        if ($buyPerUsd <= 0 || $sellPerUsd <= 0) {
            throw new RuntimeException('iraqsm.com fx.json missing parallel buy/sell.');
        }

        if ($sellPerUsd < $buyPerUsd) {
            [$buyPerUsd, $sellPerUsd] = [$sellPerUsd, $buyPerUsd];
        }

        $usdMid = ($buyPerUsd + $sellPerUsd) / 2;
        $buyFactor = $usdMid > 0 ? ($buyPerUsd / $usdMid) : 1;
        $sellFactor = $usdMid > 0 ? ($sellPerUsd / $usdMid) : 1;

        $boardRates = [
            'USD' => [
                'buy' => $buyPerUsd * $quoteUnit,
                'sell' => $sellPerUsd * $quoteUnit,
            ],
        ];

        $cross = $this->fetchCrossRates($crossUrl, $timeout);
        $priority = array_keys(config('exchange.live.priority', []));

        foreach ($priority as $code) {
            $code = strtoupper((string) $code);
            if ($code === 'USD' || $code === 'IQD') {
                continue;
            }

            $unitsPerUsd = (float) ($cross[$code] ?? 0);
            if ($unitsPerUsd <= 0) {
                continue;
            }

            $midPerUnit = $usdMid / $unitsPerUsd;
            $boardMid = $midPerUnit * $quoteUnit;

            $boardRates[$code] = [
                'buy' => $boardMid * $buyFactor,
                'sell' => $boardMid * $sellFactor,
            ];
        }

        $mids = [];
        foreach ($boardRates as $code => $row) {
            $mids[$code] = (((float) $row['buy'] + (float) $row['sell']) / 2) / $quoteUnit;
        }

        return [
            'source' => (string) ($config['source_key'] ?? 'iraqsm.com'),
            'source_label' => (string) ($config['source_label'] ?? 'سوق بغداد الموازي (iraqsm)'),
            'fetched_at' => now()->toIso8601String(),
            'provider_updated_at' => data_get($fx, 'parallel.publishedAt') ?? data_get($fx, 'asOf'),
            'rates' => $mids,
            'board_rates' => $boardRates,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFxJson(string $url, int $timeout): array
    {
        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'dinar-now-exchange-rates/1.0'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('iraqsm.com fx.json returned HTTP '.$response->status());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('iraqsm.com fx.json returned invalid JSON.');
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
