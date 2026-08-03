<?php

namespace Tests\Unit\Monitor;

use App\Monitor\Services\LogRetentionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogRetentionServiceTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'monitor-retention-' . uniqid();
        File::makeDirectory($this->logPath, 0755, true);
        config(['monitor.log_path' => $this->logPath]);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->logPath)) {
            File::deleteDirectory($this->logPath);
        }

        parent::tearDown();
    }

    public function test_clean_deletes_old_daily_logs(): void
    {
        $old = $this->logPath . DIRECTORY_SEPARATOR . '2020-01-01.log';
        $recent = $this->logPath . DIRECTORY_SEPARATOR . now()->format('Y-m-d') . '.log';
        file_put_contents($old, '{}');
        file_put_contents($recent, '{}');

        $deleted = app(LogRetentionService::class)->clean(30);

        $this->assertSame(1, $deleted);
        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
    }
}
