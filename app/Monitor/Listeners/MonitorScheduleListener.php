<?php

namespace App\Monitor\Listeners;

use App\Monitor\Services\JsonLineWriter;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class MonitorScheduleListener
{
    protected array $started = [];

    public function __construct(
        protected JsonLineWriter $writer
    ) {
    }

    public function handleStarting(CommandStarting $event): void
    {
        if (!config('monitor.enabled', true) || !config('monitor.capture_console', true)) {
            return;
        }

        if (in_array($event->command, config('monitor.ignore_commands', []), true)) {
            return;
        }

        $this->started[$event->command] = microtime(true);
    }

    public function handleFinished(CommandFinished $event): void
    {
        try {
            if (!config('monitor.enabled', true) || !config('monitor.capture_console', true)) {
                return;
            }

            if (in_array($event->command, config('monitor.ignore_commands', []), true)) {
                return;
            }

            $start = $this->started[$event->command] ?? microtime(true);
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            $this->writer->appendDaily([
                'type' => 'schedule',
                'command' => $event->command,
                'started_at' => isset($this->started[$event->command])
                    ? now()->subMilliseconds((int) $durationMs)->toIso8601String()
                    : null,
                'finished_at' => now()->toIso8601String(),
                'duration_ms' => $durationMs,
                'exit_code' => $event->exitCode,
            ]);

            unset($this->started[$event->command]);
        } catch (\Throwable) {
            // fail silently
        }
    }
}
