<?php

namespace Database\Seeders;

use App\Models\GoldRate;
use App\Services\GoldRateService;
use Illuminate\Database\Seeder;

class GoldRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'code' => 'GOLD_21K',
                'name' => 'ذهب عيار 21',
                'unit' => 'gram',
                'buy_rate' => 48500,
                'sell_rate' => 49200,
                'change_percentage' => 0.35,
                'sort_order' => 1,
            ],
            [
                'code' => 'GOLD_18K',
                'name' => 'ذهب عيار 18',
                'unit' => 'gram',
                'buy_rate' => 41600,
                'sell_rate' => 42200,
                'change_percentage' => 0.20,
                'sort_order' => 2,
            ],
            [
                'code' => 'GOLD_OUNCE',
                'name' => 'أونصة ذهب عالمية',
                'unit' => 'ounce',
                'buy_rate' => 3420000,
                'sell_rate' => 3450000,
                'change_percentage' => -0.12,
                'sort_order' => 3,
            ],
        ];

        foreach ($rates as $rate) {
            GoldRate::query()->updateOrCreate(
                ['code' => $rate['code']],
                array_merge($rate, ['is_active' => true]),
            );
        }

        app(GoldRateService::class)->clearCache();
    }
}
