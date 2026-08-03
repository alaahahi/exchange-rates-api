<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class LogViewerService
{
    public function logPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * @return array{
     *   exists: bool,
     *   path: string,
     *   size: int,
     *   size_label: string,
     *   modified_at: string|null,
     *   content: string,
     *   lines: int
     * }
     */
    public function summary(int $maxBytes = 120_000): array
    {
        $path = $this->logPath();
        $exists = is_file($path);
        $size = $exists ? (int) filesize($path) : 0;
        $modified = $exists ? date('Y-m-d H:i:s', (int) filemtime($path)) : null;
        $content = $exists ? $this->tail($path, $maxBytes) : '';

        return [
            'exists' => $exists,
            'path' => $path,
            'size' => $size,
            'size_label' => $this->formatBytes($size),
            'modified_at' => $modified,
            'content' => $content,
            'lines' => $content === '' ? 0 : substr_count($content, "\n") + 1,
        ];
    }

    public function clear(): void
    {
        $path = $this->logPath();
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('تعذر الوصول لمجلد السجلات.');
        }

        if (file_put_contents($path, '') === false) {
            throw new RuntimeException('تعذر تفريغ ملف السجل.');
        }

        Log::info('admin.logs.cleared', [
            'by' => auth()->id(),
        ]);
    }

    private function tail(string $path, int $maxBytes): string
    {
        $size = filesize($path);
        if ($size === false || $size === 0) {
            return '';
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        try {
            if ($size <= $maxBytes) {
                $content = stream_get_contents($handle);

                return is_string($content) ? $content : '';
            }

            fseek($handle, -$maxBytes, SEEK_END);
            $content = stream_get_contents($handle);
            if (! is_string($content)) {
                return '';
            }

            $firstBreak = strpos($content, "\n");
            if ($firstBreak !== false) {
                $content = substr($content, $firstBreak + 1);
            }

            return "… [truncated older log lines] …\n".$content;
        } finally {
            fclose($handle);
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
