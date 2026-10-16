<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\GoldRate;
use App\Models\Tenant;
use App\Services\ExchangeRateService;
use App\Services\MarketSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        MarketSummaryService $marketSummary,
        ExchangeRateService $exchangeRateService,
    ): View|RedirectResponse {
        $user = auth()->user();
        if ($user && $user->isTenantAdmin()) {
            return redirect()->route('admin.office.edit');
        }

        return view('admin.dashboard', [
            'exchangeCount' => ExchangeRate::query()->count(),
            'activeExchangeCount' => ExchangeRate::query()->active()->count(),
            'goldCount' => GoldRate::query()->count(),
            'activeGoldCount' => GoldRate::query()->active()->count(),
            'usd' => ExchangeRate::query()->where('currency_code', 'USD')->first(),
            'market' => $marketSummary->getSummary(),
            'sourceMeta' => $exchangeRateService->sourceMeta(),
            'latestExchangeUpdate' => ExchangeRate::query()->max('updated_at'),
            'latestGoldUpdate' => GoldRate::query()->max('updated_at'),
            'tenantCount' => Tenant::query()->where('is_primary', false)->count(),
        ]);
    }
}
