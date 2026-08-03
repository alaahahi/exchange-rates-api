<?php

namespace App\Monitor\Services;

use Illuminate\Support\Facades\File;

class LogReader
{
    public function logDirectory(): string
    {
        return config('monitor.log_path', storage_path('logs/monitor'));
    }

    public function readDailyLog(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');
        $path = $this->logDirectory() . DIRECTORY_SEPARATOR . $date . '.log';

        return $this->readJsonLines($path);
    }

    public function readAlerts(): array
    {
        $path = $this->logDirectory() . DIRECTORY_SEPARATOR . 'alerts.log';

        return $this->readJsonLines($path);
    }

    public function listDailyFiles(): array
    {
        $dir = $this->logDirectory();
        if (!File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($file) => preg_match('/^\d{4}-\d{2}-\d{2}\.log$/', $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getFilename())
            ->values()
            ->map(fn ($file) => $file->getFilename())
            ->all();
    }

    protected function readJsonLines(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $records = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $records[] = $decoded;
            }
        }

        return $records;
    }
}
