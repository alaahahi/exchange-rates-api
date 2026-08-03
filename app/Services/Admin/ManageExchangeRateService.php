<?php

namespace App\Services\Admin;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageExchangeRateService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExchangeRate $rate, array $data): ExchangeRate
    {
        return DB::transaction(function () use ($rate, $data) {
            $rate->fill([
                'currency_name' => $data['currency_name'],
                'buy_rate' => $data['buy_rate'],
                'sell_rate' => $data['sell_rate'],
                'change_percentage' => $data['change_percentage'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sort_order' => $data['sort_order'],
            ]);
            $rate->save();

            $this->exchangeRateService->clearCache();

            Log::info('admin.exchange_rate.updated', [
                'currency' => $rate->currency_code,
                'buy' => $rate->buy_rate,
                'sell' => $rate->sell_rate,
                'user_id' => auth()->id(),
            ]);

            return $rate->refresh();
        });
    }
}
