<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeRateResource;
use App\Services\ExchangeRateService;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
        private readonly TenantService $tenants,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $slug = $request->query('slug');
        $slug = is_string($slug) ? $slug : null;
        $location = $request->query('location');
        $location = is_string($location) ? $location : null;

        try {
            $tenant = $this->tenants->resolve($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'المكتب غير موجود.',
            ], 404);
        }

        $rates = $this->exchangeRateService->getActiveRates($tenant, $location);
        $meta = $this->exchangeRateService->sourceMeta($location);
        $meta['tenant'] = [
            'slug' => $tenant->is_primary ? null : $tenant->slug,
            'price_mode' => $tenant->price_mode,
        ];

        return response()
            ->json([
                'success' => true,
                'meta' => $meta,
                'data' => ExchangeRateResource::collection($rates)->resolve(),
            ])
            ->header('Cache-Control', 'public, max-age=120');
    }
}
