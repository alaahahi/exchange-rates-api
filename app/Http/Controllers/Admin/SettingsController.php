<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
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
}
