<?php

namespace App\Console\Commands;

use App\Services\SyncExchangeRatesService;
use Illuminate\Console\Command;
use Throwable;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'rates:sync
        {--force : Bypass sync TTL and refresh provider cache}
        {--location= : Governorate id (erbil, baghdad, ...)}
        {--all : Sync every configured governorate}';

    protected $description = 'Fetch live exchange rates from the configured provider and update the database';

    public function handle(SyncExchangeRatesService $sync): int
    {
        if (! config('exchange.live.enabled', true)) {
            $this->warn('Live exchange sync is disabled (EXCHANGE_LIVE_ENABLED=false).');

            return self::SUCCESS;
        }

        try {
            if ($this->option('all')) {
                $result = $sync->syncAllLocations((bool) $this->option('force'));
                $this->info('Synced locations: '.implode(', ', $result['synced'] ?: ['—']));
                foreach ($result['failed'] as $location => $message) {
                    $this->warn("{$location}: {$message}");
                }

                return self::SUCCESS;
            }

            $result = $sync->sync(
                (bool) $this->option('force'),
                is_string($this->option('location')) ? $this->option('location') : null,
            );
        } catch (Throwable $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Synced %d currencies from %s (%s) [%s].',
            $result['updated'],
            $result['source_label'],
            $result['source'],
            $result['location'] ?? 'default',
        ));

        return self::SUCCESS;
    }
}
