<?php

namespace Tests\Feature\Monitor;

use App\Monitor\Services\JsonLineWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestLoggingTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-request-' . uniqid();
        config([
            'monitor.enabled' => true,
            'monitor.log_path' => $this->logPath,
            'monitor.capture_web' => true,
        ]);

        Route::middleware('web')->get('/monitor-test-ping', function () {
            return response('pong', 200);
        })->name('monitor.test.ping');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_web_request_is_logged_to_jsonl(): void
    {
        $this->get('/monitor-test-ping')->assertOk();

        $file = $this->logPath . DIRECTORY_SEPARATOR . now()->format('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertNotEmpty($lines);

        $record = json_decode(end($lines), true);
        $this->assertSame('request', $record['type']);
        $this->assertStringContainsString('monitor-test-ping', $record['url']);
        $this->assertSame(200, $record['status']);
    }

    public function test_exception_monitor_logs_query_exceptions(): void
    {
        $writer = app(JsonLineWriter::class);
        config(['monitor.log_path' => $this->logPath]);

        $exception = new \Illuminate\Database\QueryException(
            'sqlite',
            'SELECT 1',
            [],
            new \PDOException('too many connections')
        );

        app(\App\Monitor\Services\ExceptionMonitor::class)->log($exception);

        $file = $this->logPath . DIRECTORY_SEPARATOR . now()->format('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $record = json_decode(file_get_contents($file), true);
        $this->assertSame('exception', $record['type']);
        $this->assertSame('SELECT 1', $record['sql']);
    }
}
