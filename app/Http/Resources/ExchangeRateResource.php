<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->currency_code,
            'name' => $this->currency_name,
            'buy' => (float) $this->buy_rate,
            'sell' => (float) $this->sell_rate,
            'change' => $this->change_percentage !== null
                ? (float) $this->change_percentage
                : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
