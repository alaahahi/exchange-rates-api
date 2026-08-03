<?php

namespace App\Services\RateProviders;

use App\Contracts\ExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenErApiProvider implements ExchangeRateProvider
{
    public function fetchMidRatesInIqd(): array
    {
        $config = config('exchange.providers.open_er_api', []);
        $url = (string) ($config['base_url'] ?? 'https://open.er-api.com/v6/latest/USD');
        $timeout = (int) config('exchange.live.http_timeout', 12);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get($url)
            ->throw();

        $payload = $response->json();
        if (($payload['result'] ?? null) !== 'success' || ! is_array($payload['rates'] ?? null)) {
            throw new RuntimeException('Open ER API returned an invalid payload.');
        }

        $rates = $payload['rates'];
        $iqdPerUsd = (float) ($rates['IQD'] ?? 0);
        if ($iqdPerUsd <= 0) {
            throw new RuntimeException('Open ER API did not return a valid USD/IQD rate.');
        }

        $mid = [
            'USD' => $iqdPerUsd,
        ];

        foreach ($rates as $code => $unitsPerUsd) {
            $code = strtoupper((string) $code);

            // IQD is the quote currency — never listed as a tradable row.
            if ($code === 'IQD' || $code === 'USD') {
                continue;
            }

            $unitsPerUsd = (float) $unitsPerUsd;
            if ($unitsPerUsd <= 0) {
                continue;
            }

            // API: 1 USD = X foreign units → 1 foreign unit in IQD = IQD_per_USD / X
            $mid[$code] = $iqdPerUsd / $unitsPerUsd;
        }

        return [
            'source' => (string) ($config['source_key'] ?? 'open.er-api.com'),
            'source_label' => (string) ($config['source_label'] ?? 'ExchangeRate-API'),
            'fetched_at' => now()->toIso8601String(),
            'provider_updated_at' => $payload['time_last_update_utc'] ?? null,
            'rates' => $mid,
        ];
    }
}
