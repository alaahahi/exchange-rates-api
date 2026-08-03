<?php

namespace App\Monitor\Console;

use App\Monitor\Services\LogRetentionService;
use Illuminate\Console\Command;

class CleanMonitorLogsCommand extends Command
{
    protected $signature = 'monitor:clean {--days= : Retention days override}';

    protected $description = 'Delete monitor JSONL log files older than retention period';

    public function handle(LogRetentionService $retention): int
    {
        $days = $this->option('days');
        $deleted = $retention->clean($days !== null ? (int) $days : null);
        $this->info("Deleted {$deleted} monitor log file(s).");

        return self::SUCCESS;
    }
}
