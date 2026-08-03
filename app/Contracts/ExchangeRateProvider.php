<?php

namespace App\Contracts;

interface ExchangeRateProvider
{
    /**
     * Mid-market IQD price for 1 unit of each foreign currency.
     *
     * @return array{
     *   source: string,
     *   source_label: string,
     *   fetched_at: string,
     *   rates: array<string, float>
     * }
     */
    public function fetchMidRatesInIqd(): array;
}
