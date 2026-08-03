<?php

namespace App\Monitor\Services;

class AlertEvaluator
{
    public function __construct(
        protected JsonLineWriter $writer
    ) {
    }

    public function evaluateRequest(array $requestRecord): void
    {
        $thresholds = config('monitor.alert_thresholds', []);
        $alerts = [];

        $db = $requestRecord['database'] ?? null;
        if ($db && isset($thresholds['threads_connected'])) {
            $threads = (int) ($db['threads_connected'] ?? 0);
            if ($threads > (int) $thresholds['threads_connected']) {
                $alerts[] = [
                    'metric' => 'threads_connected',
                    'value' => $threads,
                    'threshold' => (int) $thresholds['threads_connected'],
                ];
            }
        }

        $duration = (float) ($requestRecord['execution_time_ms'] ?? 0);
        if ($duration > (int) ($thresholds['response_time_ms'] ?? PHP_INT_MAX)) {
            $alerts[] = [
                'metric' => 'response_time_ms',
                'value' => $duration,
                'threshold' => (int) $thresholds['response_time_ms'],
            ];
        }

        $memoryMb = ((int) ($requestRecord['peak_memory'] ?? 0)) / 1048576;
        if ($memoryMb > (int) ($thresholds['memory_mb'] ?? PHP_INT_MAX)) {
            $alerts[] = [
                'metric' => 'memory_mb',
                'value' => round($memoryMb, 2),
                'threshold' => (int) $thresholds['memory_mb'],
            ];
        }

        $queryTime = (float) ($requestRecord['queries']['query_time_ms'] ?? 0);
        if ($queryTime > (int) ($thresholds['query_time_ms'] ?? PHP_INT_MAX)) {
            $alerts[] = [
                'metric' => 'query_time_ms',
                'value' => $queryTime,
                'threshold' => (int) $thresholds['query_time_ms'],
            ];
        }

        foreach ($alerts as $alert) {
            $this->writer->appendAlert(array_merge($alert, [
                'source' => 'request',
                'url' => $requestRecord['url'] ?? null,
                'route' => $requestRecord['route'] ?? null,
            ]));
        }
    }
}
