<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateExchangeRateRequest;
use App\Models\ExchangeRate;
use App\Services\Admin\ManageExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    public function index(): View
    {
        return view('admin.exchange-rates.index', [
            'rates' => ExchangeRate::query()->ordered()->get(),
        ]);
    }

    public function update(
        UpdateExchangeRateRequest $request,
        ExchangeRate $exchangeRate,
        ManageExchangeRateService $service,
    ): RedirectResponse {
        $service->update($exchangeRate, $request->validated());

        return back()->with('success', 'تم تحديث سعر '.$exchangeRate->currency_code.' بنجاح.');
    }
}
