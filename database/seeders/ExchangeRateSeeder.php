<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Iraqi parallel (street) board — only these currencies, as provided by market boards.
        // IQD is never listed (quote currency only).
        $rates = [
            ['currency_code' => 'USD', 'currency_name' => 'دولار أمريكي', 'buy_rate' => 152050, 'sell_rate' => 152300, 'sort_order' => 1],
            ['currency_code' => 'EUR', 'currency_name' => 'يورو', 'buy_rate' => 113.5, 'sell_rate' => 114.25, 'sort_order' => 2],
            ['currency_code' => 'GBP', 'currency_name' => 'جنيه إسترليني', 'buy_rate' => 132.25, 'sell_rate' => 133.25, 'sort_order' => 3],
            ['currency_code' => 'TRY', 'currency_name' => 'ليرة تركية', 'buy_rate' => 4780, 'sell_rate' => 4690, 'sort_order' => 4],
            ['currency_code' => 'IRR', 'currency_name' => 'تومان إيراني', 'buy_rate' => 18000000, 'sell_rate' => 15000000, 'sort_order' => 5],
            ['currency_code' => 'NOK', 'currency_name' => 'كرونة نرويجية', 'buy_rate' => 86, 'sell_rate' => 90, 'sort_order' => 6],
            ['currency_code' => 'SEK', 'currency_name' => 'كرونة سويدية', 'buy_rate' => 87.5, 'sell_rate' => 92, 'sort_order' => 7],
            ['currency_code' => 'JOD', 'currency_name' => 'دينار أردني', 'buy_rate' => 71, 'sell_rate' => 68, 'sort_order' => 8],
            ['currency_code' => 'SAR', 'currency_name' => 'ريال سعودي', 'buy_rate' => 385, 'sell_rate' => 370, 'sort_order' => 9],
            ['currency_code' => 'AED', 'currency_name' => 'درهم إماراتي', 'buy_rate' => 377, 'sell_rate' => 367, 'sort_order' => 10],
            ['currency_code' => 'CAD', 'currency_name' => 'دولار كندي', 'buy_rate' => 69, 'sell_rate' => 70.5, 'sort_order' => 11],
            ['currency_code' => 'AUD', 'currency_name' => 'دولار أسترالي', 'buy_rate' => 67.75, 'sell_rate' => 68.75, 'sort_order' => 12],
            ['currency_code' => 'CHF', 'currency_name' => 'فرنك سويسري', 'buy_rate' => 121, 'sell_rate' => 123, 'sort_order' => 13],
            ['currency_code' => 'DKK', 'currency_name' => 'كرونة دنماركية', 'buy_rate' => 136, 'sell_rate' => 141, 'sort_order' => 14],
            ['currency_code' => 'QAR', 'currency_name' => 'ريال قطري', 'buy_rate' => 390, 'sell_rate' => 375, 'sort_order' => 15],
            ['currency_code' => 'KWD', 'currency_name' => 'دينار كويتي', 'buy_rate' => 280, 'sell_rate' => 315, 'sort_order' => 16],
        ];

        $codes = array_column($rates, 'currency_code');

        ExchangeRate::query()->where('currency_code', 'IQD')->delete();

        foreach ($rates as $rate) {
            ExchangeRate::query()->updateOrCreate(
                ['currency_code' => $rate['currency_code']],
                [
                    'currency_name' => $rate['currency_name'],
                    'buy_rate' => $rate['buy_rate'],
                    'sell_rate' => $rate['sell_rate'],
                    'change_percentage' => 0,
                    'is_active' => true,
                    'sort_order' => $rate['sort_order'],
                    'source' => 'iraq-parallel-market',
                ],
            );
        }

        ExchangeRate::query()
            ->whereNotIn('currency_code', $codes)
            ->update(['is_active' => false]);

        app(ExchangeRateService::class)->clearCache();
        Cache::forget(\App\Services\SyncExchangeRatesService::LAST_SYNC_CACHE_KEY);
        Cache::forget(\App\Services\SyncExchangeRatesService::RAW_CACHE_KEY);
    }
}
