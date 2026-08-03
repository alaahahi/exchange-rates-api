<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MarketSummaryService;
use Illuminate\Http\JsonResponse;

class MarketSummaryController extends Controller
{
    public function __construct(
        private readonly MarketSummaryService $marketSummaryService,
    ) {}

    public function show(): JsonResponse
    {
        return response()
            ->json([
                'success' => true,
                'data' => $this->marketSummaryService->getSummary(),
            ])
            ->header('Cache-Control', 'public, max-age=30');
    }
}
