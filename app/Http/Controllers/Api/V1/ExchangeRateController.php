<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeRateResource;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function index(): JsonResponse
    {
        $rates = $this->exchangeRateService->getActiveRates();

        return response()
            ->json([
                'success' => true,
                'meta' => $this->exchangeRateService->sourceMeta(),
                'data' => ExchangeRateResource::collection($rates)->resolve(),
            ])
            ->header('Cache-Control', 'public, max-age=30');
    }
}
