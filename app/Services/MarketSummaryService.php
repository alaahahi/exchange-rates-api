<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;

class MarketSummaryService
{
    /**
     * @return array{
     *   is_open: bool,
     *   status: string,
     *   status_label: string,
     *   timezone: string,
     *   local_time: string,
     *   usd: array{buy: float, sell: float, spread: float, change: float|null, updated_at: string|null}|null
     * }
     */
    public function getSummary(): array
    {
        $timezone = (string) config('exchange.market_timezone', 'Asia/Baghdad');
        $now = CarbonImmutable::now($timezone);

        $openHour = (int) config('exchange.market_open_hour', 9);
        $closeHour = (int) config('exchange.market_close_hour', 17);
        $openDays = config('exchange.market_open_days', [0, 1, 2, 3, 4, 6]); // Sun–Thu + Sat

        $dayOfWeek = $now->dayOfWeek; // 0 Sunday … 6 Saturday
        $hour = (int) $now->format('G');
        $isOpenDay = in_array($dayOfWeek, $openDays, true);
        $isOpenHour = $hour >= $openHour && $hour < $closeHour;
        $isOpen = $isOpenDay && $isOpenHour;

        $usd = ExchangeRate::query()
            ->active()
            ->where('currency_code', 'USD')
            ->first();

        $usdPayload = null;
        if ($usd) {
            $buy = (float) $usd->buy_rate;
            $sell = (float) $usd->sell_rate;
            $usdPayload = [
                'buy' => $buy,
                'sell' => $sell,
                'spread' => round($sell - $buy, 4),
                'change' => $usd->change_percentage !== null
                    ? (float) $usd->change_percentage
                    : null,
                'updated_at' => $usd->updated_at?->toIso8601String(),
            ];
        }

        return [
            'is_open' => $isOpen,
            'status' => $isOpen ? 'open' : 'closed',
            'status_label' => $isOpen ? 'السوق مفتوح' : 'السوق مغلق',
            'timezone' => $timezone,
            'local_time' => $now->toIso8601String(),
            'usd' => $usdPayload,
        ];
    }
}
