<?php

namespace App\Monitor\Services;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class ExceptionMonitor
{
    public function __construct(
        protected JsonLineWriter $writer
    ) {
    }

    public function shouldLog(Throwable $e): bool
    {
        if ($e instanceof QueryException || $e instanceof PDOException) {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'too many connections')
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'max connect errors');
    }

    public function log(Throwable $e): void
    {
        if (!config('monitor.enabled', true) || !$this->shouldLog($e)) {
            return;
        }

        try {
            $sql = null;
            $bindings = [];
            if ($e instanceof QueryException) {
                $sql = $e->getSql();
                $bindings = $e->getBindings();
            }

            $request = request();
            $this->writer->appendDaily([
                'type' => 'exception',
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'sql' => $sql,
                'bindings' => $bindings,
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip' => $request?->ip(),
                'user_id' => optional($request?->user())->id,
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Throwable) {
            // fail silently
        }
    }
}
