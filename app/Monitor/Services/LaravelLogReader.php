<?php

namespace App\Monitor\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LaravelLogReader
{
    public function logDirectory(): string
    {
        return rtrim(config('monitor.laravel_log.path', storage_path('logs')), DIRECTORY_SEPARATOR);
    }

    public function listFiles(): array
    {
        if (!config('monitor.laravel_log.enabled', true)) {
            return [];
        }

        $dir = $this->logDirectory();
        if (!File::isDirectory($dir)) {
            return [];
        }

        $patterns = config('monitor.laravel_log.patterns', ['laravel.log', 'laravel-*.log']);
        $files = [];

        foreach ($patterns as $pattern) {
            $matches = glob($dir . DIRECTORY_SEPARATOR . $pattern) ?: [];
            foreach ($matches as $path) {
                if (!is_file($path) || !$this->isAllowedPath($path)) {
                    continue;
                }
                $files[basename($path)] = [
                    'file' => basename($path),
                    'size' => filesize($path) ?: 0,
                    'modified' => date('c', filemtime($path) ?: time()),
                ];
            }
        }

        uasort($files, fn ($a, $b) => strcmp($b['modified'], $a['modified']));

        return array_values($files);
    }

    public function readEntries(array $options = []): array
    {
        if (!config('monitor.laravel_log.enabled', true)) {
            return ['file' => null, 'entries' => [], 'total' => 0];
        }

        $file = $options['file'] ?? $this->defaultFile();
        $level = strtoupper((string) ($options['level'] ?? ''));
        $search = trim((string) ($options['search'] ?? ''));
        $limit = min(
            (int) ($options['limit'] ?? config('monitor.laravel_log.default_limit', 100)),
            (int) config('monitor.laravel_log.max_limit', 500)
        );

        $path = $this->resolveFilePath($file);
        if (!$path || !File::exists($path)) {
            return ['file' => $file, 'entries' => [], 'total' => 0];
        }

        $content = $this->readTail($path);
        $entries = $this->parseEntries($content);

        if ($level !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($entry) => strtoupper($entry['level'] ?? '') === $level
            ));
        }

        if ($search !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($entry) => stripos($entry['message'] ?? '', $search) !== false
                    || stripos($entry['raw'] ?? '', $search) !== false
            ));
        }

        $total = count($entries);
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = min(
            max(1, (int) ($options['per_page'] ?? $limit)),
            (int) config('monitor.laravel_log.max_limit', 500)
        );

        // Newest first for Hub pagination
        $entries = array_reverse($entries);
        $offset = ($page - 1) * $perPage;
        $pageEntries = array_slice($entries, $offset, $perPage);

        return [
            'file' => basename($path),
            'entries' => $pageEntries,
            'total' => $total,
            'limit' => $perPage,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function summary(int $limit = 20): array
    {
        $result = $this->readEntries(['limit' => $limit]);
        $levels = [];

        foreach ($result['entries'] as $entry) {
            $level = strtoupper($entry['level'] ?? 'UNKNOWN');
            $levels[$level] = ($levels[$level] ?? 0) + 1;
        }

        return [
            'file' => $result['file'],
            'available_files' => $this->listFiles(),
            'recent' => $result['entries'],
            'levels' => $levels,
            'total_matched' => $result['total'],
        ];
    }

    protected function defaultFile(): string
    {
        $files = $this->listFiles();
        if (!empty($files)) {
            return $files[0]['file'];
        }

        $dated = 'laravel-' . now()->format('Y-m-d') . '.log';
        if (File::exists($this->logDirectory() . DIRECTORY_SEPARATOR . $dated)) {
            return $dated;
        }

        return 'laravel.log';
    }

    protected function resolveFilePath(?string $file): ?string
    {
        if (!$file) {
            return null;
        }

        $file = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file));
        $path = $this->logDirectory() . DIRECTORY_SEPARATOR . $file;

        return $this->isAllowedPath($path) ? $path : null;
    }

    protected function isAllowedPath(string $path): bool
    {
        $realDir = realpath($this->logDirectory());
        $realFile = realpath($path);

        if (!$realDir || !$realFile || !is_file($realFile)) {
            return false;
        }

        return str_starts_with($realFile, $realDir . DIRECTORY_SEPARATOR)
            || $realFile === $realDir;
    }

    protected function readTail(string $path): string
    {
        $maxBytes = (int) config('monitor.laravel_log.max_read_bytes', 2097152);
        $size = filesize($path);

        if ($size === false || $size <= $maxBytes) {
            return (string) file_get_contents($path);
        }

        $handle = fopen($path, 'rb');
        if (!$handle) {
            return '';
        }

        fseek($handle, -$maxBytes, SEEK_END);
        $content = fread($handle, $maxBytes);
        fclose($handle);

        return (string) $content;
    }

    protected function parseEntries(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([^.]+)\.([A-Z]+):\s*(.*)$/', $line, $matches)) {
                if ($current) {
                    $entries[] = $this->finalizeEntry($current);
                }

                $current = [
                    'timestamp' => $matches[1],
                    'channel' => trim($matches[2]),
                    'level' => strtoupper(trim($matches[3])),
                    'message' => $matches[4],
                    'raw_lines' => [$line],
                ];
                continue;
            }

            if ($current) {
                $current['raw_lines'][] = $line;
                $current['message'] .= PHP_EOL . $line;
            }
        }

        if ($current) {
            $entries[] = $this->finalizeEntry($current);
        }

        return $entries;
    }

    protected function finalizeEntry(array $entry): array
    {
        $message = trim($entry['message']);
        $context = null;

        if (preg_match('/^(.+?)(\s+\{.*\})$/s', $message, $matches)) {
            $message = trim($matches[1]);
            $context = $matches[2];
        }

        return [
            'timestamp' => $entry['timestamp'],
            'channel' => $entry['channel'],
            'level' => $entry['level'],
            'message' => Str::limit($message, 2000),
            'context' => $context,
            'raw' => implode(PHP_EOL, $entry['raw_lines']),
        ];
    }
}
