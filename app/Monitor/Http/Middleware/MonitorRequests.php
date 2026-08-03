<?php

namespace App\Monitor\Http\Middleware;

use App\Monitor\Services\AlertEvaluator;
use App\Monitor\Services\DbStatusService;
use App\Monitor\Services\JsonLineWriter;
use App\Monitor\Services\QueryStatsCollector;
use App\Monitor\Support\MonitorContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorRequests
{
    public function __construct(
        protected MonitorContext $context,
        protected QueryStatsCollector $queries,
        protected JsonLineWriter $writer,
        protected DbStatusService $dbStatus,
        protected AlertEvaluator $alerts
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('monitor.enabled', true)) {
            return $next($request);
        }

        $this->context->reset();
        $this->queries->reset();

        if ($this->shouldSkip($request)) {
            $this->context->shouldLog = false;
            return $next($request);
        }

        $route = $request->route();
        $this->context->routeName = $route?->getName();
        $action = $route?->getActionName();
        $this->context->controller = is_string($action) ? $action : null;

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            if (!config('monitor.enabled', true) || !$this->context->shouldLog) {
                return;
            }

            if ($this->context->startedAt <= 0) {
                return;
            }

            $durationMs = round((microtime(true) - $this->context->startedAt) * 1000, 2);
            $maxDuration = (int) config('monitor.max_request_duration_ms', 300000);

            if ($durationMs < 0 || $durationMs > $maxDuration) {
                return;
            }
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;

            $dbSnapshot = null;
            if (config('monitor.snapshot_db_every_request', true) || $this->context->dbTouched) {
                $dbSnapshot = $this->dbStatus->snapshot();
            }

            $record = [
                'type' => 'request',
                'url' => $request->fullUrl(),
                'route' => $this->context->routeName,
                'controller' => $this->context->controller,
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_id' => optional($request->user())->id,
                'execution_time_ms' => $durationMs,
                'memory' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'status' => $status,
                'queries' => $this->queries->summary(),
                'database' => $dbSnapshot,
            ];

            $this->writer->appendDaily($record);
            $this->alerts->evaluateRequest($record);
        } catch (\Throwable) {
            // fail silently
        } finally {
            $this->context->reset();
            $this->queries->reset();
        }
    }

    protected function shouldSkip(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, config('monitor.ignore_routes', []), true)) {
            return true;
        }

        $path = ltrim($request->path(), '/');
        foreach (config('monitor.ignore_path_prefixes', []) as $prefix) {
            $prefix = trim($prefix, '/');
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
