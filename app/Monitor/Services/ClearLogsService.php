<?php

namespace App\Monitor\Services;

use Illuminate\Support\Facades\File;

class ClearLogsService
{
    /**
     * @param  list<string>  $targets  laravel|monitor|alerts|all
     * @return array{cleared: array<string, mixed>, errors: list<string>}
     */
    public function clear(array $targets): array
    {
        $targets = array_values(array_unique(array_map('strtolower', $targets)));
        if (in_array('all', $targets, true)) {
            $targets = ['laravel', 'monitor', 'alerts'];
        }

        $cleared = [];
        $errors = [];

        foreach ($targets as $target) {
            try {
                $cleared[$target] = match ($target) {
                    'laravel' => $this->clearLaravelLogs(),
                    'monitor' => $this->clearMonitorLogs(),
                    'alerts' => $this->clearAlertsLog(),
                    default => throw new \InvalidArgumentException("Unknown target: {$target}"),
                };
            } catch (\Throwable $e) {
                $errors[] = "{$target}: ".$e->getMessage();
            }
        }

        return compact('cleared', 'errors');
    }

    /**
     * @return array{files: list<string>, bytes_freed: int}
     */
    protected function clearLaravelLogs(): array
    {
        $reader = app(LaravelLogReader::class);
        $files = $reader->listFiles();
        $cleared = [];
        $bytes = 0;

        foreach ($files as $meta) {
            $path = $reader->logDirectory().DIRECTORY_SEPARATOR.$meta['file'];
            if (! is_file($path)) {
                continue;
            }
            $size = filesize($path) ?: 0;
            File::put($path, '');
            $cleared[] = $meta['file'];
            $bytes += $size;
        }

        return ['files' => $cleared, 'bytes_freed' => $bytes];
    }

    /**
     * @return array{files: list<string>, count: int}
     */
    protected function clearMonitorLogs(): array
    {
        $dir = config('monitor.log_path', storage_path('logs/monitor'));
        $cleared = [];

        if (! File::isDirectory($dir)) {
            return ['files' => [], 'count' => 0];
        }

        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if ($name === 'alerts.log') {
                continue;
            }
            File::delete($file->getPathname());
            $cleared[] = $name;
        }

        return ['files' => $cleared, 'count' => count($cleared)];
    }

    /**
     * @return array{file: string, bytes_freed: int}
     */
    protected function clearAlertsLog(): array
    {
        $path = rtrim(config('monitor.log_path', storage_path('logs/monitor')), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'alerts.log';

        $bytes = is_file($path) ? (filesize($path) ?: 0) : 0;
        File::put($path, '');

        return ['file' => 'alerts.log', 'bytes_freed' => $bytes];
    }
}
