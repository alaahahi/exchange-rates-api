<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\Admin\ManageTenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly ManageTenantService $tenants,
    ) {}

    public function index(): View
    {
        return view('admin.tenants.index', [
            'tenants' => Tenant::query()
                ->where('is_primary', false)
                ->orderBy('name')
                ->get(),
            'frontendUrl' => rtrim((string) config('services.frontend.url', env('FRONTEND_URL', 'https://dinar-now.com')), '/'),
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->tenants->create($data, $request->file('logo'));

        return back()->with('success', 'تم إنشاء المكتب بنجاح.');
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->tenants->update($tenant, $data, $request->file('logo'));

        return back()->with('success', 'تم تحديث المكتب.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->tenants->delete($tenant);

        return back()->with('success', 'تم حذف المكتب.');
    }
}
