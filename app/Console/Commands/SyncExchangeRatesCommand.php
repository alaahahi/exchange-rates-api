<?php

namespace App\Console\Commands;

use App\Services\SyncExchangeRatesService;
use Illuminate\Console\Command;
use Throwable;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'rates:sync {--force : Bypass sync TTL and refresh provider cache}';

    protected $description = 'Fetch live exchange rates from the configured provider and update the database';

    public function handle(SyncExchangeRatesService $sync): int
    {
        if (! config('exchange.live.enabled', true)) {
            $this->warn('Live exchange sync is disabled (EXCHANGE_LIVE_ENABLED=false).');

            return self::SUCCESS;
        }

        try {
            $result = $sync->sync((bool) $this->option('force'));
        } catch (Throwable $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Synced %d currencies from %s (%s).',
            $result['updated'],
            $result['source_label'],
            $result['source'],
        ));

        return self::SUCCESS;
    }
}
