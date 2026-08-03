<?php

namespace App\Monitor\Services;

use Illuminate\Support\Facades\File;

class LogRetentionService
{
    public function clean(?int $days = null): int
    {
        $days = $days ?? (int) config('monitor.retention_days', 30);
        $dir = config('monitor.log_path', storage_path('logs/monitor'));
        if (!File::isDirectory($dir)) {
            return 0;
        }

        $cutoff = now()->subDays($days)->startOfDay();
        $deleted = 0;

        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})\.log$/', $name, $matches)) {
                continue;
            }

            $fileDate = \Carbon\Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
            if ($fileDate->lt($cutoff)) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }
}
