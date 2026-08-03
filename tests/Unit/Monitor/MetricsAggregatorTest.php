<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\MetricsAggregator;
use Tests\TestCase;

class MetricsAggregatorTest extends TestCase
{
    public function test_aggregate_computes_summary_and_charts(): void
    {
        config(['monitor.slow_request_threshold_ms' => 1000]);

        $records = [
            [
                'type' => 'request',
                'timestamp' => '2026-07-16T10:00:00+00:00',
                'execution_time_ms' => 500,
                'status' => 200,
                'peak_memory' => 52428800,
                'database' => ['threads_connected' => 10],
                'queries' => ['slow_queries' => []],
            ],
            [
                'type' => 'request',
                'timestamp' => '2026-07-16T10:01:00+00:00',
                'execution_time_ms' => 1500,
                'status' => 500,
                'peak_memory' => 62914560,
                'database' => ['threads_connected' => 25],
                'queries' => [
                    'slow_queries' => [
                        ['sql' => 'SELECT 1', 'time_ms' => 600],
                    ],
                ],
                'url' => '/api/test',
                'route' => 'api.test',
            ],
            [
                'type' => 'exception',
                'timestamp' => '2026-07-16T10:02:00+00:00',
                'message' => 'SQL error',
            ],
            [
                'type' => 'queue',
                'job' => 'App\\Jobs\\Example',
            ],
        ];

        $metrics = app(MetricsAggregator::class)->aggregate($records);

        $this->assertSame(2, $metrics['summary']['total_requests']);
        $this->assertSame(1000.0, $metrics['summary']['avg_response_ms']);
        $this->assertSame(1, $metrics['summary']['slow_requests_count']);
        $this->assertSame(1, $metrics['summary']['failed_requests_count']);
        $this->assertSame(1, $metrics['summary']['exceptions_count']);
        $this->assertSame(25, $metrics['summary']['threads_connected']);
        $this->assertCount(1, $metrics['slow_queries']);
        $this->assertNotEmpty($metrics['requests_per_minute']['labels']);
    }
}
