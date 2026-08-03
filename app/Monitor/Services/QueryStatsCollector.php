<?php

namespace App\Monitor\Services;

class QueryStatsCollector
{
    protected int $count = 0;

    protected float $totalTimeMs = 0;

    /** @var array<int, array{sql:string,bindings:array,time_ms:float}> */
    protected array $slowQueries = [];

    public function record(string $sql, array $bindings, float $timeMs): void
    {
        $this->count++;
        $this->totalTimeMs += $timeMs;

        $threshold = (int) config('monitor.slow_query_threshold_ms', 500);
        if ($timeMs >= $threshold) {
            $this->slowQueries[] = [
                'sql' => $sql,
                'bindings' => $bindings,
                'time_ms' => round($timeMs, 2),
            ];
        }
    }

    public function summary(): array
    {
        return [
            'query_count' => $this->count,
            'query_time_ms' => round($this->totalTimeMs, 2),
            'slow_queries' => $this->slowQueries,
        ];
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->totalTimeMs = 0;
        $this->slowQueries = [];
    }
}
