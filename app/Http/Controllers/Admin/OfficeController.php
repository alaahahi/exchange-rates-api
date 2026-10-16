<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOwnTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRatesRequest;
use App\Models\ExchangeRate;
use App\Models\Tenant;
use App\Services\Admin\ManageTenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function __construct(
        private readonly ManageTenantService $tenants,
    ) {}

    public function edit(Request $request): View|RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'لا يوجد مكتب مرتبط بحسابك.');
        }

        $panelRates = ExchangeRate::query()->active()->ordered()->get();
        $overrides = $tenant->rateOverrides()->get()->keyBy('currency_code');

        return view('admin.office.edit', [
            'tenant' => $tenant,
            'panelRates' => $panelRates,
            'overrides' => $overrides,
            'frontendUrl' => rtrim((string) config('services.frontend.url', env('FRONTEND_URL', 'https://dinar-now.com')), '/'),
        ]);
    }

    public function update(UpdateOwnTenantRequest $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return back()->with('error', 'لا يوجد مكتب مرتبط بحسابك.');
        }

        $this->tenants->updateOwnSettings($tenant, $request->validated(), $request->file('logo'));

        return back()->with('success', 'تم حفظ إعدادات المكتب.');
    }

    public function updateRates(UpdateTenantRatesRequest $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return back()->with('error', 'لا يوجد مكتب مرتبط بحسابك.');
        }

        if ($tenant->price_mode !== Tenant::PRICE_CUSTOM) {
            return back()->with('error', 'فعّل «أسعاري» أولاً قبل تعديل الأسعار.');
        }

        $this->tenants->syncOverrides($tenant, $request->validated('rates'));

        return back()->with('success', 'تم حفظ أسعار المكتب.');
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if ($user->isTenantAdmin() && $user->tenant_id) {
            return Tenant::query()
                ->where('id', $user->tenant_id)
                ->where('is_primary', false)
                ->first();
        }

        // Platform admin can preview via ?tenant_id=
        if ($user->isPlatformAdmin() && $request->filled('tenant_id')) {
            return Tenant::query()
                ->where('id', (int) $request->query('tenant_id'))
                ->where('is_primary', false)
                ->first();
        }

        return null;
    }
}
