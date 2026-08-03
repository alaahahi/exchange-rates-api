<?php

namespace App\Monitor\Services;

class MetricsAggregator
{
    public function aggregate(array $records): array
    {
        $requests = array_values(array_filter($records, fn ($r) => ($r['type'] ?? '') === 'request'));
        $exceptions = array_values(array_filter($records, fn ($r) => ($r['type'] ?? '') === 'exception'));
        $queues = array_values(array_filter($records, fn ($r) => in_array($r['type'] ?? '', ['queue', 'queue_failed'], true)));
        $schedules = array_values(array_filter($records, fn ($r) => ($r['type'] ?? '') === 'schedule'));

        $durations = array_values(array_filter(
            array_map(fn ($r) => (float) ($r['execution_time_ms'] ?? 0), $requests),
            fn ($ms) => $ms > 0 && $ms <= (int) config('monitor.max_request_duration_ms', 300000)
        ));
        $avgResponse = count($durations) ? round(array_sum($durations) / count($durations), 2) : 0;

        $slowThreshold = (int) config('monitor.slow_request_threshold_ms', 2000);
        $maxDuration = (int) config('monitor.max_request_duration_ms', 300000);
        $slowRequests = array_values(array_filter(
            $requests,
            fn ($r) => ($ms = (float) ($r['execution_time_ms'] ?? 0)) >= $slowThreshold && $ms <= $maxDuration
        ));

        $failedRequests = array_values(array_filter(
            $requests,
            fn ($r) => (int) ($r['status'] ?? 0) >= 500
        ));

        $slowQueries = [];
        foreach ($requests as $request) {
            foreach ($request['queries']['slow_queries'] ?? [] as $slow) {
                $slowQueries[] = array_merge($slow, [
                    'url' => $request['url'] ?? null,
                    'route' => $request['route'] ?? null,
                ]);
            }
        }

        $threadsConnected = 0;
        $maxConnectionsToday = 0;
        foreach ($requests as $request) {
            $current = (int) ($request['database']['threads_connected'] ?? 0);
            $threadsConnected = max($threadsConnected, $current);
            $maxConnectionsToday = max($maxConnectionsToday, $current);
        }

        $latestDb = null;
        for ($i = count($requests) - 1; $i >= 0; $i--) {
            if (!empty($requests[$i]['database'])) {
                $latestDb = $requests[$i]['database'];
                break;
            }
        }

        $requestsPerMinute = $this->requestsPerMinute($requests);
        $memoryTrend = $this->memoryTrend($requests);

        return [
            'summary' => [
                'total_requests' => count($requests),
                'avg_response_ms' => $avgResponse,
                'slow_requests_count' => count($slowRequests),
                'failed_requests_count' => count($failedRequests),
                'exceptions_count' => count($exceptions),
                'queue_jobs_count' => count(array_filter($queues, fn ($q) => ($q['type'] ?? '') === 'queue')),
                'queue_failed_count' => count(array_filter($queues, fn ($q) => ($q['type'] ?? '') === 'queue_failed')),
                'scheduler_runs' => count($schedules),
                'threads_connected' => (int) ($latestDb['threads_connected'] ?? $threadsConnected),
                'max_connections_today' => $maxConnectionsToday,
            ],
            'requests_per_minute' => $requestsPerMinute,
            'memory_trend' => $memoryTrend,
            'slow_requests' => array_slice(array_reverse($slowRequests), 0, 50),
            'slow_queries' => array_slice(array_reverse($slowQueries), 0, 50),
            'exceptions' => array_slice(array_reverse($exceptions), 0, 50),
            'queue_jobs' => array_slice(array_reverse($queues), 0, 50),
            'scheduler_history' => array_slice(array_reverse($schedules), 0, 50),
            'latest_database' => $latestDb,
        ];
    }

    protected function requestsPerMinute(array $requests): array
    {
        $buckets = [];
        foreach ($requests as $request) {
            $ts = $request['timestamp'] ?? null;
            if (!$ts) {
                continue;
            }
            $minute = substr($ts, 0, 16);
            $buckets[$minute] = ($buckets[$minute] ?? 0) + 1;
        }
        ksort($buckets);

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
        ];
    }

    protected function memoryTrend(array $requests): array
    {
        $labels = [];
        $values = [];
        $slice = array_slice($requests, -60);

        foreach ($slice as $request) {
            $labels[] = substr((string) ($request['timestamp'] ?? ''), 11, 8);
            $values[] = round(((int) ($request['peak_memory'] ?? 0)) / 1048576, 2);
        }

        return compact('labels', 'values');
    }
}
