<?php

namespace App\Monitor\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithMonitorApi
{
    protected function monitorJson(array $data, int $status = 200): JsonResponse
    {
        $response = response()->json(array_merge([
            'project' => config('monitor.project_name'),
            'hostname' => gethostname() ?: php_uname('n'),
            'environment' => app()->environment(),
            'server_time' => now()->toIso8601String(),
        ], $data), $status);

        $origin = config('monitor.cors_origin');
        if ($origin) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
        }

        return $response;
    }
}
