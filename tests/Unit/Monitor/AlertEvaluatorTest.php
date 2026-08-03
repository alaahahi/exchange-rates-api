<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\AlertEvaluator;
use App\Monitor\Services\JsonLineWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AlertEvaluatorTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-alert-' . uniqid();
        config([
            'monitor.log_path' => $this->logPath,
            'monitor.enabled' => true,
            'monitor.alert_thresholds' => [
                'threads_connected' => 50,
                'response_time_ms' => 1000,
                'memory_mb' => 64,
                'query_time_ms' => 500,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_evaluate_request_writes_alerts_when_thresholds_exceeded(): void
    {
        $evaluator = app(AlertEvaluator::class);

        $evaluator->evaluateRequest([
            'url' => '/slow',
            'route' => 'slow.route',
            'execution_time_ms' => 2500,
            'peak_memory' => 80 * 1048576,
            'database' => ['threads_connected' => 120],
            'queries' => ['query_time_ms' => 800],
        ]);

        $alertsFile = $this->logPath . DIRECTORY_SEPARATOR . 'alerts.log';
        $this->assertFileExists($alertsFile);

        $lines = file($alertsFile, FILE_IGNORE_NEW_LINES);
        $this->assertGreaterThanOrEqual(3, count($lines));

        $metrics = array_map(fn ($line) => json_decode($line, true)['metric'], $lines);
        $this->assertContains('threads_connected', $metrics);
        $this->assertContains('response_time_ms', $metrics);
        $this->assertContains('memory_mb', $metrics);
        $this->assertContains('query_time_ms', $metrics);
    }
}
