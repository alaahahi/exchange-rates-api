<?php

namespace Tests\Feature\Api\V1;

use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_rates_endpoint_returns_success_payload(): void
    {
        config(['exchange.live.enabled' => false]);

        $this->seed(\Database\Seeders\ExchangeRateSeeder::class);

        $response = $this->getJson('/api/v1/exchange-rates');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'meta' => ['source', 'source_label', 'live_enabled', 'last_synced_at'],
                'data' => [
                    '*' => ['currency', 'name', 'buy', 'sell', 'quote_unit', 'change', 'source', 'updated_at'],
                ],
            ]);

        $data = collect($response->json('data'));
        $this->assertSame('USD', $data->first()['currency']);
        $this->assertSame(152050, (int) $data->first()['buy']);
        $this->assertSame(152300, (int) $data->first()['sell']);
        $this->assertNull($data->firstWhere('currency', 'IQD'));
        $this->assertCount(16, $data);
    }

    public function test_live_sync_imports_all_provider_currencies_without_iqd(): void
    {
        config([
            'exchange.live.enabled' => true,
            'exchange.live.provider' => 'iraqsm',
            'exchange.live.sync_ttl' => 1,
            'exchange.live.buy_spread_percent' => 0,
            'exchange.live.sell_spread_percent' => 0,
        ]);

        Http::fake([
            'iraqsm.com/*' => Http::response([
                'kind' => 'fx',
                'asOf' => '2026-10-10',
                'unit' => 'IQD per 1 USD',
                'parallel' => [
                    'buy' => 1640,
                    'sell' => 1641,
                    'stale' => false,
                    'publishedAt' => '2026-10-10T12:14:47.000Z',
                ],
                'official' => ['cbi' => 1510, 'confirmed' => '2026-10-07'],
            ], 200),
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'provider' => 'https://www.exchangerate-api.com',
                'base_code' => 'USD',
                'time_last_update_utc' => 'Sat, 01 Aug 2026 00:00:00 +0000',
                'rates' => [
                    'IQD' => 1310.0,
                    'EUR' => 0.87,
                    'TRY' => 47.5,
                    'GBP' => 0.74,
                    'AED' => 3.6725,
                    'SAR' => 3.75,
                    'JPY' => 150.0,
                    'CAD' => 1.35,
                ],
            ], 200),
        ]);

        $result = app(\App\Services\SyncExchangeRatesService::class)->sync(true);
        $this->assertSame(8, $result['updated']);

        $this->assertDatabaseMissing('exchange_rates', ['currency_code' => 'IQD']);
        $this->assertSame(8, ExchangeRate::query()->active()->count());

        $usd = ExchangeRate::query()->where('currency_code', 'USD')->first();
        $this->assertNotNull($usd);
        $this->assertSame('iraqsm.com', $usd->source);
        $this->assertEqualsWithDelta(164000, (float) $usd->buy_rate, 1);
        $this->assertEqualsWithDelta(164100, (float) $usd->sell_rate, 1);

        config(['exchange.live.enabled' => false]);

        $response = $this->getJson('/api/v1/exchange-rates');
        $response->assertOk()
            ->assertJsonPath('meta.source', 'iraqsm.com');

        $codes = collect($response->json('data'))->pluck('currency');
        $this->assertTrue($codes->contains('JPY'));
        $this->assertTrue($codes->contains('CAD'));
        $this->assertTrue($codes->contains('USD'));
        $this->assertFalse($codes->contains('IQD'));
    }
}
