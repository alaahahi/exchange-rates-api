<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_rates_endpoint_returns_success_payload(): void
    {
        $this->seed(\Database\Seeders\ExchangeRateSeeder::class);

        $response = $this->getJson('/api/v1/exchange-rates');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['currency', 'name', 'buy', 'sell', 'change', 'updated_at'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertSame('USD', $response->json('data.0.currency'));
    }
}
