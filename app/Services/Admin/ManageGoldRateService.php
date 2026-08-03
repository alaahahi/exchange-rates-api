<?php

namespace App\Services\Admin;

use App\Models\GoldRate;
use App\Services\GoldRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageGoldRateService
{
    public function __construct(
        private readonly GoldRateService $goldRateService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GoldRate $rate, array $data): GoldRate
    {
        return DB::transaction(function () use ($rate, $data) {
            $rate->fill([
                'name' => $data['name'],
                'buy_rate' => $data['buy_rate'],
                'sell_rate' => $data['sell_rate'],
                'change_percentage' => $data['change_percentage'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sort_order' => $data['sort_order'],
            ]);
            $rate->save();

            $this->goldRateService->clearCache();

            Log::info('admin.gold_rate.updated', [
                'code' => $rate->code,
                'buy' => $rate->buy_rate,
                'sell' => $rate->sell_rate,
                'user_id' => auth()->id(),
            ]);

            return $rate->refresh();
        });
    }
}
