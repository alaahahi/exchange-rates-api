<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\TenantRateOverride;
use Database\Seeders\ExchangeRateSeeder;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_endpoint_returns_primary_brand_without_slug(): void
    {
        $this->seed(TenantSeeder::class);

        $this->getJson('/api/v1/site')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.show_ads', true)
            ->assertJsonPath('data.name', 'dinar-now');
    }

    public function test_office_slug_returns_custom_rates_when_enabled(): void
    {
        config(['exchange.live.enabled' => false]);

        $this->seed([TenantSeeder::class, ExchangeRateSeeder::class]);

        $office = Tenant::query()->create([
            'name' => 'مكتب النجم',
            'slug' => 'al-najm',
            'price_mode' => Tenant::PRICE_CUSTOM,
            'is_primary' => false,
            'is_active' => true,
        ]);

        TenantRateOverride::query()->create([
            'tenant_id' => $office->id,
            'currency_code' => 'USD',
            'buy_rate' => 160000,
            'sell_rate' => 161000,
        ]);

        $this->getJson('/api/v1/site?slug=al-najm')
            ->assertOk()
            ->assertJsonPath('data.slug', 'al-najm')
            ->assertJsonPath('data.show_ads', false)
            ->assertJsonPath('data.is_primary', false);

        $response = $this->getJson('/api/v1/exchange-rates?slug=al-najm');
        $response->assertOk()->assertJsonPath('success', true);

        $usd = collect($response->json('data'))->firstWhere('currency', 'USD');
        $this->assertNotNull($usd);
        $this->assertSame(160000, (int) $usd['buy']);
        $this->assertSame(161000, (int) $usd['sell']);
        $this->assertSame('tenant', $usd['source']);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->seed(TenantSeeder::class);

        $this->getJson('/api/v1/site?slug=missing-office')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
