<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['currency_code' => 'USD', 'currency_name' => 'دولار أمريكي', 'buy_rate' => 150000, 'sell_rate' => 151000, 'change_percentage' => 0.25, 'sort_order' => 1],
            ['currency_code' => 'EUR', 'currency_name' => 'يورو', 'buy_rate' => 162000, 'sell_rate' => 164000, 'change_percentage' => -0.10, 'sort_order' => 2],
            ['currency_code' => 'TRY', 'currency_name' => 'ليرة تركية', 'buy_rate' => 4400, 'sell_rate' => 4600, 'change_percentage' => 0.05, 'sort_order' => 3],
            ['currency_code' => 'GBP', 'currency_name' => 'جنيه إسترليني', 'buy_rate' => 190000, 'sell_rate' => 193000, 'change_percentage' => 0.15, 'sort_order' => 4],
            ['currency_code' => 'AED', 'currency_name' => 'درهم إماراتي', 'buy_rate' => 40800, 'sell_rate' => 41200, 'change_percentage' => 0.00, 'sort_order' => 5],
            ['currency_code' => 'SAR', 'currency_name' => 'ريال سعودي', 'buy_rate' => 39900, 'sell_rate' => 40300, 'change_percentage' => -0.05, 'sort_order' => 6],
            ['currency_code' => 'IQD', 'currency_name' => 'دينار عراقي', 'buy_rate' => 1, 'sell_rate' => 1, 'change_percentage' => 0.00, 'sort_order' => 7],
        ];

        foreach ($rates as $rate) {
            ExchangeRate::query()->updateOrCreate(
                ['currency_code' => $rate['currency_code']],
                array_merge($rate, ['is_active' => true]),
            );
        }

        app(ExchangeRateService::class)->clearCache();
    }
}
