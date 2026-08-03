<?php

namespace App\Monitor\Listeners;

use App\Monitor\Services\JsonLineWriter;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class MonitorQueueListener
{
    protected array $started = [];

    public function __construct(
        protected JsonLineWriter $writer
    ) {
    }

    public function handleProcessing(JobProcessing $event): void
    {
        if (!config('monitor.enabled', true) || !config('monitor.capture_queue', true)) {
            return;
        }

        $id = $this->jobId($event->job);
        $this->started[$id] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
        ];
    }

    public function handleProcessed(JobProcessed $event): void
    {
        $this->logJob($this->jobId($event->job), $event->connectionName, $event->job->resolveName(), 'queue', null);
    }

    public function handleFailed(JobFailed $event): void
    {
        $this->logJob(
            $this->jobId($event->job),
            $event->connectionName,
            $event->job->resolveName(),
            'queue_failed',
            $event->exception?->getMessage()
        );
    }

    protected function logJob($id, ?string $connection, string $jobName, string $type, ?string $error): void
    {
        try {
            if (!config('monitor.enabled', true) || !config('monitor.capture_queue', true)) {
                return;
            }

            $start = $this->started[$id] ?? ['time' => microtime(true), 'memory' => memory_get_usage(true)];
            $durationMs = round((microtime(true) - $start['time']) * 1000, 2);

            $this->writer->appendDaily([
                'type' => $type,
                'queue' => $connection,
                'job' => $jobName,
                'runtime_ms' => $durationMs,
                'memory' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'error' => $error,
            ]);

            unset($this->started[$id]);
        } catch (\Throwable) {
            // fail silently
        }
    }

    protected function jobId($job): string
    {
        if (is_object($job) && method_exists($job, 'getJobId')) {
            $id = $job->getJobId();

            return $id ? (string) $id : spl_object_hash($job);
        }

        return is_object($job) ? spl_object_hash($job) : (string) $job;
    }
}
