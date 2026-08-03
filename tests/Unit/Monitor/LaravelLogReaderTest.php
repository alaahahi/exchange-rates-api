<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\LaravelLogReader;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LaravelLogReaderTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-log-test-' . uniqid();
        File::makeDirectory($this->logPath, 0755, true);

        config([
            'monitor.laravel_log.enabled' => true,
            'monitor.laravel_log.path' => $this->logPath,
            'monitor.laravel_log.patterns' => ['laravel.log', 'laravel-*.log'],
            'monitor.laravel_log.default_limit' => 50,
        ]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_list_files_and_parse_entries(): void
    {
        $content = <<<LOG
[2026-07-16 10:00:01] local.INFO: App started
[2026-07-16 10:00:02] local.ERROR: Database failed
Stack trace here
[2026-07-16 10:00:03] local.WARNING: Slow query detected
LOG;

        file_put_contents($this->logPath . DIRECTORY_SEPARATOR . 'laravel.log', $content);

        $reader = app(LaravelLogReader::class);
        $files = $reader->listFiles();

        $this->assertCount(1, $files);
        $this->assertSame('laravel.log', $files[0]['file']);

        $result = $reader->readEntries(['file' => 'laravel.log']);
        $this->assertCount(3, $result['entries']);
        $this->assertSame('ERROR', $result['entries'][1]['level']);
        $this->assertStringContainsString('Stack trace here', $result['entries'][1]['message']);
    }

    public function test_filters_by_level(): void
    {
        file_put_contents(
            $this->logPath . DIRECTORY_SEPARATOR . 'laravel.log',
            "[2026-07-16 10:00:01] local.INFO: ok\n[2026-07-16 10:00:02] local.ERROR: fail\n"
        );

        $reader = app(LaravelLogReader::class);
        $result = $reader->readEntries(['file' => 'laravel.log', 'level' => 'ERROR']);

        $this->assertCount(1, $result['entries']);
        $this->assertSame('ERROR', $result['entries'][0]['level']);
    }

    public function test_rejects_path_traversal(): void
    {
        $reader = app(LaravelLogReader::class);
        $result = $reader->readEntries(['file' => '../outside.log']);

        $this->assertSame([], $result['entries']);
    }
}
