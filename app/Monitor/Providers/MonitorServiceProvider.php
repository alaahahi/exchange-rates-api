<?php

namespace App\Monitor\Providers;

use App\Monitor\Console\CleanMonitorLogsCommand;
use App\Monitor\Http\Middleware\MonitorAdmin;
use App\Monitor\Http\Middleware\MonitorRequests;
use App\Monitor\Listeners\MonitorQueueListener;
use App\Monitor\Listeners\MonitorScheduleListener;
use App\Monitor\Services\AlertEvaluator;
use App\Monitor\Services\DbStatusService;
use App\Monitor\Services\ExceptionMonitor;
use App\Monitor\Services\JsonLineWriter;
use App\Monitor\Services\LaravelLogReader;
use App\Monitor\Services\LogReader;
use App\Monitor\Services\LogRetentionService;
use App\Monitor\Services\MetricsAggregator;
use App\Monitor\Services\QueryStatsCollector;
use App\Monitor\Support\MonitorContext;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/monitor.php'), 'monitor');

        $this->app->scoped(MonitorContext::class);
        $this->app->scoped(QueryStatsCollector::class);
        $this->app->singleton(JsonLineWriter::class);
        $this->app->singleton(DbStatusService::class);
        $this->app->singleton(AlertEvaluator::class);
        $this->app->singleton(LogReader::class);
        $this->app->singleton(LaravelLogReader::class);
        $this->app->singleton(MetricsAggregator::class);
        $this->app->singleton(LogRetentionService::class);
        $this->app->singleton(ExceptionMonitor::class);
    }

    public function boot(): void
    {
        $this->publishes([
            base_path('config/monitor.php') => config_path('monitor.php'),
        ], 'monitor-config');

        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerQueryListener();
        $this->registerQueueListeners();
        $this->registerConsoleListeners();
        $this->registerRetentionCleanup();

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanMonitorLogsCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $apiMiddleware = config('monitor.api_middleware', []);
        $statusMiddleware = config('monitor.status_middleware', []);
        $dashboardMiddleware = config('monitor.dashboard_middleware', []);

        Route::middleware($apiMiddleware)
            ->prefix('monitor/api')
            ->group(function () {
                Route::get('/status', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'status'])
                    ->name('monitor.api.status');
                Route::get('/metrics', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'metrics'])
                    ->name('monitor.api.metrics');
                Route::get('/alerts', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'alerts'])
                    ->name('monitor.api.alerts');
                Route::get('/logs', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'logs'])
                    ->name('monitor.api.logs');
                Route::get('/dates', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'dates'])
                    ->name('monitor.api.dates');
                Route::get('/overview', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'overview'])
                    ->name('monitor.api.overview');
                Route::get('/laravel-logs', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'laravelLogs'])
                    ->name('monitor.api.laravel-logs');
                Route::get('/laravel-log-files', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'laravelLogFiles'])
                    ->name('monitor.api.laravel-log-files');
                Route::post('/clear-logs', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'clearLogs'])
                    ->name('monitor.api.clear-logs');
            });

        Route::middleware($statusMiddleware)
            ->get('/monitor/status', [\App\Monitor\Http\Controllers\MonitorApiController::class, 'status'])
            ->name('monitor.status');

        Route::middleware($dashboardMiddleware)
            ->get('/monitor/dashboard', \App\Monitor\Http\Controllers\DashboardController::class)
            ->name('monitor.dashboard');
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('monitor.admin', MonitorAdmin::class);

        if (config('monitor.capture_web', true)) {
            $router->pushMiddlewareToGroup('web', MonitorRequests::class);
        }

        if (config('monitor.capture_api', true)) {
            $router->pushMiddlewareToGroup('api', MonitorRequests::class);
        }
    }

    protected function registerQueryListener(): void
    {
        DB::listen(function ($query) {
            try {
                if (!config('monitor.enabled', true)) {
                    return;
                }

                $context = app(MonitorContext::class);
                $context->dbTouched = true;

                app(QueryStatsCollector::class)->record(
                    $query->sql,
                    $query->bindings ?? [],
                    (float) $query->time
                );
            } catch (\Throwable) {
                // fail silently
            }
        });
    }

    protected function registerQueueListeners(): void
    {
        $listener = $this->app->make(MonitorQueueListener::class);
        Event::listen(JobProcessing::class, [$listener, 'handleProcessing']);
        Event::listen(JobProcessed::class, [$listener, 'handleProcessed']);
        Event::listen(JobFailed::class, [$listener, 'handleFailed']);
    }

    protected function registerConsoleListeners(): void
    {
        $listener = $this->app->make(MonitorScheduleListener::class);
        Event::listen(CommandStarting::class, [$listener, 'handleStarting']);
        Event::listen(CommandFinished::class, [$listener, 'handleFinished']);
    }

    protected function registerRetentionCleanup(): void
    {
        try {
            $cacheKey = 'monitor:last_retention_cleanup';
            if (\Illuminate\Support\Facades\Cache::get($cacheKey)) {
                return;
            }

            app(LogRetentionService::class)->clean();
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDay());
        } catch (\Throwable) {
            // fail silently
        }
    }
}
