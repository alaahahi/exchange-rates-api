<?php

namespace Tests\Feature\Api\V1;

use Database\Seeders\ExchangeRateSeeder;
use Database\Seeders\GoldRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoldAndMarketTest extends TestCase
{
    use RefreshDatabase;

    public function test_gold_rates_endpoint_returns_payload(): void
    {
        $this->seed(GoldRateSeeder::class);

        $response = $this->getJson('/api/v1/gold-rates');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['code', 'name', 'unit', 'buy', 'sell', 'change', 'updated_at'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_market_summary_endpoint_returns_status_and_usd(): void
    {
        $this->seed(ExchangeRateSeeder::class);

        $response = $this->getJson('/api/v1/market-summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'is_open',
                    'status',
                    'status_label',
                    'timezone',
                    'local_time',
                    'usd' => ['buy', 'sell', 'spread', 'change', 'updated_at'],
                ],
            ]);
    }
}
