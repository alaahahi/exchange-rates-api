<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SettingsService
{
    /**
     * @return array{ran: bool, output: string, pending_before: int}
     */
    public function runMigrations(): array
    {
        $pendingBefore = $this->pendingMigrationsCount();

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
        } catch (Throwable $e) {
            Log::error('admin.settings.migrate_failed', [
                'message' => $e->getMessage(),
                'by' => auth()->id(),
            ]);

            throw new RuntimeException('فشل تشغيل المايغريشن: '.$e->getMessage(), 0, $e);
        }

        Log::info('admin.settings.migrate_ran', [
            'pending_before' => $pendingBefore,
            'by' => auth()->id(),
            'output' => $output,
        ]);

        return [
            'ran' => true,
            'output' => $output !== '' ? $output : 'Nothing to migrate.',
            'pending_before' => $pendingBefore,
        ];
    }

    public function pendingMigrationsCount(): int
    {
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
        } catch (Throwable) {
            return 0;
        }

        return substr_count($output, 'Pending');
    }

    /**
     * @return list<string>
     */
    public function pendingMigrationNames(): array
    {
        try {
            Artisan::call('migrate:status');
            $lines = preg_split('/\R/', Artisan::output()) ?: [];
        } catch (Throwable) {
            return [];
        }

        $pending = [];
        foreach ($lines as $line) {
            if (! str_contains($line, 'Pending')) {
                continue;
            }
            if (preg_match('/\d{4}_\d{2}_\d{2}_\d{6}_\S+/', $line, $match)) {
                $pending[] = $match[0];
            }
        }

        return $pending;
    }
}
