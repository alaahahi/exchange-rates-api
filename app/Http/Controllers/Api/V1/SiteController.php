<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private readonly TenantService $tenants,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $slug = $request->query('slug');
        $slug = is_string($slug) ? $slug : null;

        try {
            $tenant = $this->tenants->resolve($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'المكتب غير موجود.',
            ], 404);
        }

        return response()
            ->json([
                'success' => true,
                'data' => $tenant->toSitePayload(),
            ])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
