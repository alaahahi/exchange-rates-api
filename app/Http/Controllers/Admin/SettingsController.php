<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GoogleAnalyticsReportService;
use App\Services\Admin\LogViewerService;
use App\Services\Admin\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly LogViewerService $logs,
        private readonly GoogleAnalyticsReportService $analytics,
    ) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'logoUrl' => asset('logo.png'),
            'logoExists' => is_file(public_path('logo.png')),
            'pendingCount' => $this->settings->pendingMigrationsCount(),
            'pendingMigrations' => $this->settings->pendingMigrationNames(),
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'provider' => config('exchange.live.provider'),
            'sourceLabel' => config('exchange.live.source_label'),
            'cacheTtl' => (int) config('exchange.cache_ttl', 120),
            'liveEnabled' => (bool) config('exchange.live.enabled', true),
            'log' => $this->logs->summary(),
            'ga' => $this->analytics->dashboard(7),
            'gaDashboardUrl' => (string) config('services.google.analytics_url'),
        ]);
    }

    public function migrate(): RedirectResponse
    {
        try {
            $result = $this->settings->runMigrations();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $result['pending_before'] > 0
            ? 'تم تشغيل المايغريشن بنجاح.'
            : 'لا توجد مايغريشن معلّقة. النظام محدّث.';

        return back()
            ->with('success', $message)
            ->with('migrate_output', $result['output']);
    }

    public function clearLogs(): RedirectResponse
    {
        try {
            $this->logs->clear();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم تفريغ سجل الأخطاء.');
    }
}
