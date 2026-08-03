<?php

namespace App\Services\RateProviders;

use App\Contracts\ExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QamarAlFajrProvider implements ExchangeRateProvider
{
    /**
     * Qamar board labels IQD for the USD/IQD quote (Iraqi board convention).
     *
     * @var array<string, string>
     */
    private const LABEL_TO_CODE = [
        'IQD' => 'USD',
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'TRY' => 'TRY',
        'IRR' => 'IRR',
        'NOK' => 'NOK',
        'SEK' => 'SEK',
        'JOD' => 'JOD',
        'SAR' => 'SAR',
        'AED' => 'AED',
        'CAD' => 'CAD',
        'AUD' => 'AUD',
        'CHF' => 'CHF',
        'DKK' => 'DKK',
        'QAR' => 'QAR',
        'KWD' => 'KWD',
    ];

    public function fetchMidRatesInIqd(): array
    {
        $config = config('exchange.providers.qamar', []);
        $url = (string) ($config['base_url'] ?? 'https://qamaralfajr.com/production/exchange_rates.php');
        $timeout = (int) config('exchange.live.http_timeout', 12);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => 'dinar-now-exchange-rates/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Qamar exchange page returned HTTP '.$response->status());
        }

        $boardRates = $this->parseBoardHtml($response->body());
        if ($boardRates === []) {
            throw new RuntimeException('Qamar exchange page could not be parsed.');
        }

        // Mid per 1 unit (for compatibility with mid-based consumers).
        $quoteUnit = max(1, (int) config('exchange.live.quote_unit', 100));
        $mids = [];
        foreach ($boardRates as $code => $row) {
            $mids[$code] = (((float) $row['buy'] + (float) $row['sell']) / 2) / $quoteUnit;
        }

        return [
            'source' => (string) ($config['source_key'] ?? 'qamaralfajr.com'),
            'source_label' => (string) ($config['source_label'] ?? 'قمر الفجر للصيرفة'),
            'fetched_at' => now()->toIso8601String(),
            'rates' => $mids,
            'board_rates' => $boardRates,
        ];
    }

    /**
     * @return array<string, array{buy: float, sell: float}>
     */
    private function parseBoardHtml(string $html): array
    {
        $pattern = '/>\s*(\d+(?:\.\d+)?)\s*<\/button><\/td>\s*<td[^>]*>\s*<button[^>]*>\s*(\d+(?:\.\d+)?)\s*<\/button>.*?([A-Za-z]{3})\s*<\/td>/si';

        if (! preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $board = [];
        foreach ($matches as $match) {
            $sell = (float) $match[1];
            $buy = (float) $match[2];
            $label = strtoupper($match[3]);
            $code = self::LABEL_TO_CODE[$label] ?? null;

            if ($code === null || $buy <= 0 || $sell <= 0) {
                continue;
            }

            // First occurrence wins (desktop table); skip mobile duplicates.
            if (isset($board[$code])) {
                continue;
            }

            $board[$code] = [
                'buy' => $buy,
                'sell' => $sell,
            ];
        }

        return $board;
    }
}
