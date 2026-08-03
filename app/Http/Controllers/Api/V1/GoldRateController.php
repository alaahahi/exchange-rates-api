<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GoldRateResource;
use App\Services\GoldRateService;
use Illuminate\Http\JsonResponse;

class GoldRateController extends Controller
{
    public function __construct(
        private readonly GoldRateService $goldRateService,
    ) {}

    public function index(): JsonResponse
    {
        $rates = $this->goldRateService->getActiveRates();

        return response()
            ->json([
                'success' => true,
                'data' => GoldRateResource::collection($rates)->resolve(),
            ])
            ->header('Cache-Control', 'public, max-age=30');
    }
}
