<?php

namespace App\Monitor\Support;

class MonitorContext
{
    public float $startedAt = 0;

    public int $startMemory = 0;

    public bool $dbTouched = false;

    public bool $shouldLog = true;

    public ?string $routeName = null;

    public ?string $controller = null;

    public function reset(): void
    {
        $this->startedAt = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->dbTouched = false;
        $this->shouldLog = true;
        $this->routeName = null;
        $this->controller = null;
    }
}
