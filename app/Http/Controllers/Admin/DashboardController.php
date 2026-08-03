<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\GoldRate;
use App\Services\MarketSummaryService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MarketSummaryService $marketSummary): View
    {
        return view('admin.dashboard', [
            'exchangeCount' => ExchangeRate::query()->count(),
            'activeExchangeCount' => ExchangeRate::query()->active()->count(),
            'goldCount' => GoldRate::query()->count(),
            'activeGoldCount' => GoldRate::query()->active()->count(),
            'usd' => ExchangeRate::query()->where('currency_code', 'USD')->first(),
            'market' => $marketSummary->getSummary(),
            'latestExchangeUpdate' => ExchangeRate::query()->max('updated_at'),
            'latestGoldUpdate' => GoldRate::query()->max('updated_at'),
        ]);
    }
}
