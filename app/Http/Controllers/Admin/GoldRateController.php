<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGoldRateRequest;
use App\Models\GoldRate;
use App\Services\Admin\ManageGoldRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoldRateController extends Controller
{
    public function index(): View
    {
        return view('admin.gold-rates.index', [
            'rates' => GoldRate::query()->ordered()->get(),
        ]);
    }

    public function update(
        UpdateGoldRateRequest $request,
        GoldRate $goldRate,
        ManageGoldRateService $service,
    ): RedirectResponse {
        $service->update($goldRate, $request->validated());

        return back()->with('success', 'تم تحديث سعر الذهب '.$goldRate->code.' بنجاح.');
    }
}
