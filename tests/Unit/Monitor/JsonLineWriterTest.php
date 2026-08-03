<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\JsonLineWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JsonLineWriterTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-test-' . uniqid();
        config(['monitor.log_path' => $this->logPath, 'monitor.enabled' => true]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_append_daily_writes_json_line(): void
    {
        $writer = app(JsonLineWriter::class);
        $writer->appendDaily(['type' => 'request', 'url' => '/test']);

        $file = $this->logPath . DIRECTORY_SEPARATOR . now()->format('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $line = json_decode(file_get_contents($file), true);
        $this->assertSame('request', $line['type']);
        $this->assertSame('/test', $line['url']);
        $this->assertArrayHasKey('timestamp', $line);
        $this->assertArrayHasKey('project', $line);
    }

    public function test_append_alert_writes_to_alerts_file(): void
    {
        $writer = app(JsonLineWriter::class);
        $writer->appendAlert(['metric' => 'memory_mb', 'value' => 200]);

        $file = $this->logPath . DIRECTORY_SEPARATOR . 'alerts.log';
        $this->assertFileExists($file);

        $line = json_decode(file_get_contents($file), true);
        $this->assertSame('alert', $line['type']);
        $this->assertSame('memory_mb', $line['metric']);
    }

    public function test_writer_respects_disabled_config(): void
    {
        config(['monitor.enabled' => false]);

        $writer = app(JsonLineWriter::class);
        $writer->appendDaily(['type' => 'request']);

        $this->assertFalse(File::isDirectory($this->logPath));
    }
}
