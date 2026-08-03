<?php

namespace App\Monitor\Services;

use Illuminate\Support\Facades\File;

class JsonLineWriter
{
    public function append(string $filename, array $record): void
    {
        try {
            if (!config('monitor.enabled', true)) {
                return;
            }

            $dir = config('monitor.log_path', storage_path('logs/monitor'));
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $record = array_merge($this->baseMeta(), $record);
            $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($line === false) {
                return;
            }

            file_put_contents(
                rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename,
                $line . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable) {
            // Never interrupt the application.
        }
    }

    public function appendDaily(array $record): void
    {
        $this->append(now()->format('Y-m-d') . '.log', $record);
    }

    public function appendAlert(array $record): void
    {
        $this->append('alerts.log', array_merge(['type' => 'alert'], $record));
    }

    protected function baseMeta(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'project' => config('monitor.project_name'),
            'hostname' => gethostname() ?: php_uname('n'),
            'environment' => app()->environment(),
        ];
    }
}
