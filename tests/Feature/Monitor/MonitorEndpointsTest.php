<?php

namespace Tests\Feature\Monitor;

use App\Monitor\Services\JsonLineWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MonitorEndpointsTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-api-' . uniqid();
        config([
            'monitor.enabled' => true,
            'monitor.log_path' => $this->logPath,
            'monitor.api_middleware' => [],
            'monitor.dashboard_middleware' => [],
            'monitor.status_middleware' => [],
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_status_endpoint_is_public(): void
    {
        $this->getJson('/monitor/status')
            ->assertOk()
            ->assertJsonStructure([
                'project',
                'hostname',
                'environment',
                'server_time',
                'status' => [
                    'threads_connected',
                    'memory',
                ],
            ]);
    }

    public function test_api_status_endpoint_is_public(): void
    {
        $this->getJson('/monitor/api/status')->assertOk();
    }

    public function test_api_overview_returns_metrics_from_logs(): void
    {
        $writer = app(JsonLineWriter::class);
        $writer->appendDaily([
            'type' => 'request',
            'url' => '/test',
            'execution_time_ms' => 120,
            'status' => 200,
            'peak_memory' => 1048576,
            'database' => ['threads_connected' => 5],
            'queries' => ['slow_queries' => []],
        ]);

        $this->getJson('/monitor/api/overview?date=' . now()->format('Y-m-d'))
            ->assertOk()
            ->assertJsonPath('metrics.summary.total_requests', 1)
            ->assertJsonStructure([
                'project',
                'date',
                'available_dates',
                'status',
                'metrics',
                'alerts',
            ]);
    }

    public function test_api_metrics_endpoint(): void
    {
        $this->getJson('/monitor/api/metrics?date=' . now()->format('Y-m-d'))
            ->assertOk()
            ->assertJsonStructure(['metrics' => ['summary']]);
    }

    public function test_api_alerts_and_logs_endpoints(): void
    {
        $writer = app(JsonLineWriter::class);
        $writer->appendAlert(['metric' => 'memory_mb', 'value' => 200, 'threshold' => 128]);
        $writer->appendDaily(['type' => 'exception', 'message' => 'db error']);

        $this->getJson('/monitor/api/alerts')->assertOk()->assertJsonPath('count', 1);
        $this->getJson('/monitor/api/logs?type=exception')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/monitor/api/dates')->assertOk()->assertJsonStructure(['dates']);
    }

    public function test_dashboard_renders_without_auth(): void
    {
        $this->get('/monitor/dashboard')
            ->assertOk()
            ->assertSee('كل البيانات من API', false);
    }

    public function test_laravel_logs_api_returns_parsed_entries(): void
    {
        $laravelDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-laravel-api-' . uniqid();
        mkdir($laravelDir, 0755, true);
        config(['monitor.laravel_log.path' => $laravelDir]);

        file_put_contents(
            $laravelDir . DIRECTORY_SEPARATOR . 'laravel.log',
            "[2026-07-16 12:00:00] local.ERROR: Central hub test\n"
        );

        $this->getJson('/monitor/api/laravel-logs?file=laravel.log')
            ->assertOk()
            ->assertJsonPath('entries.0.level', 'ERROR')
            ->assertJsonPath('entries.0.message', 'Central hub test');

        $this->getJson('/monitor/api/laravel-log-files')
            ->assertOk()
            ->assertJsonStructure(['files' => [['file', 'size', 'modified']]]);

        array_map('unlink', glob($laravelDir . '/*') ?: []);
        rmdir($laravelDir);
    }
}
