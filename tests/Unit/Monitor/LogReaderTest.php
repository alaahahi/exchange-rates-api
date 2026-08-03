<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\JsonLineWriter;
use App\Monitor\Services\LogReader;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogReaderTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-reader-' . uniqid();
        config(['monitor.log_path' => $this->logPath, 'monitor.enabled' => true]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_read_daily_log_and_list_files(): void
    {
        $writer = app(JsonLineWriter::class);
        $writer->appendDaily(['type' => 'request', 'url' => '/a']);
        $writer->appendDaily(['type' => 'request', 'url' => '/b']);

        $reader = app(LogReader::class);
        $records = $reader->readDailyLog(now()->format('Y-m-d'));

        $this->assertCount(2, $records);
        $this->assertContains(now()->format('Y-m-d') . '.log', $reader->listDailyFiles());
    }
}
