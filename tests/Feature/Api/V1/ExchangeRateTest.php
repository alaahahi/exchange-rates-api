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

        $response = $this->getJson('/api/v1/exchange-rates?location=erbil');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'meta' => ['source', 'source_label', 'live_enabled', 'last_synced_at', 'location', 'locations'],
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
        $this->assertSame('erbil', $response->json('meta.location'));
    }

    public function test_live_sync_imports_dinarlive_erbil_board(): void
    {
        config([
            'exchange.live.enabled' => true,
            'exchange.live.provider' => 'dinarlive',
            'exchange.live.location' => 'erbil',
            'exchange.live.sync_ttl' => 1,
            'exchange.live.buy_spread_percent' => 0,
            'exchange.live.sell_spread_percent' => 0,
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'dinarlive.com')) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $id = (int) ($query['id'] ?? 0);
                $map = [
                    5 => 166000,
                    6 => 186010,
                    7 => 219860,
                    8 => 3370,
                ];

                return Http::response([
                    'value' => $map[$id] ?? 0,
                    'created_at' => '2026-10-13 13:00:13',
                ], 200);
            }

            if (str_contains($url, 'open.er-api.com')) {
                return Http::response([
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
                ], 200);
            }

            return Http::response(['message' => 'unexpected url'], 500);
        });

        $result = app(\App\Services\SyncExchangeRatesService::class)->sync(true, 'erbil');
        $this->assertSame('erbil', $result['location']);
        $this->assertGreaterThanOrEqual(4, $result['updated']);

        $this->assertDatabaseMissing('exchange_rates', ['currency_code' => 'IQD']);
        $this->assertTrue(
            ExchangeRate::query()->where('location', 'erbil')->active()->exists()
        );

        $usd = ExchangeRate::query()
            ->where('currency_code', 'USD')
            ->where('location', 'erbil')
            ->first();
        $this->assertNotNull($usd);
        $this->assertSame('dinarlive.com', $usd->source);
        $this->assertEqualsWithDelta(166000, (float) $usd->buy_rate, 1);

        config(['exchange.live.enabled' => false]);

        $response = $this->getJson('/api/v1/exchange-rates?location=erbil');
        $response->assertOk()
            ->assertJsonPath('meta.source', 'dinarlive.com')
            ->assertJsonPath('meta.location', 'erbil');

        $codes = collect($response->json('data'))->pluck('currency');
        $this->assertTrue($codes->contains('USD'));
        $this->assertTrue($codes->contains('EUR'));
        $this->assertFalse($codes->contains('IQD'));
    }
}
