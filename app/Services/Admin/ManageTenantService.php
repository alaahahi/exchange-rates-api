<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use App\Models\TenantRateOverride;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageTenantService
{
    /**
     * @param  array{name: string, slug?: string|null, price_mode?: string, is_active?: bool, email?: string|null, password?: string|null}  $data
     */
    public function create(array $data, ?UploadedFile $logo = null): Tenant
    {
        return DB::transaction(function () use ($data, $logo) {
            $slug = $this->normalizeSlug($data['slug'] ?? null, $data['name']);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'price_mode' => $data['price_mode'] ?? Tenant::PRICE_PANEL,
                'is_primary' => false,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if ($logo) {
                $tenant->logo_path = $this->storeLogo($tenant, $logo);
                $tenant->save();
            }

            if (! empty($data['email']) && ! empty($data['password'])) {
                User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => User::ROLE_TENANT_ADMIN,
                    'tenant_id' => $tenant->id,
                    'email_verified_at' => now(),
                ]);
            }

            Log::info('admin.tenant.created', [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
                'by' => auth()->id(),
            ]);

            return $tenant->refresh();
        });
    }

    /**
     * @param  array{name?: string, slug?: string|null, price_mode?: string, is_active?: bool}  $data
     */
    public function update(Tenant $tenant, array $data, ?UploadedFile $logo = null): Tenant
    {
        if ($tenant->is_primary) {
            throw ValidationException::withMessages([
                'tenant' => ['لا يمكن تعديل حساب الموقع الأساسي من هنا.'],
            ]);
        }

        return DB::transaction(function () use ($tenant, $data, $logo) {
            if (isset($data['name'])) {
                $tenant->name = $data['name'];
            }
            if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
                $tenant->slug = $this->normalizeSlug($data['slug'], $tenant->name, $tenant->id);
            }
            if (isset($data['price_mode'])) {
                $tenant->price_mode = $data['price_mode'];
            }
            if (array_key_exists('is_active', $data)) {
                $tenant->is_active = (bool) $data['is_active'];
            }
            if ($logo) {
                $this->deleteLogoFile($tenant->logo_path);
                $tenant->logo_path = $this->storeLogo($tenant, $logo);
            }
            $tenant->save();

            Log::info('admin.tenant.updated', [
                'tenant_id' => $tenant->id,
                'slug' => $tenant->slug,
                'by' => auth()->id(),
            ]);

            return $tenant->refresh();
        });
    }

    /**
     * @param  array{price_mode: string}  $data
     */
    public function updateOwnSettings(Tenant $tenant, array $data, ?UploadedFile $logo = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $logo) {
            $tenant->price_mode = $data['price_mode'] ?? $tenant->price_mode;
            if ($logo) {
                $this->deleteLogoFile($tenant->logo_path);
                $tenant->logo_path = $this->storeLogo($tenant, $logo);
            }
            $tenant->save();

            Log::info('admin.tenant.own_settings_updated', [
                'tenant_id' => $tenant->id,
                'by' => auth()->id(),
            ]);

            return $tenant->refresh();
        });
    }

    /**
     * @param  array<int, array{currency_code: string, buy_rate: float|int|string, sell_rate: float|int|string}>  $rows
     */
    public function syncOverrides(Tenant $tenant, array $rows): void
    {
        DB::transaction(function () use ($tenant, $rows) {
            foreach ($rows as $row) {
                $code = strtoupper(trim((string) $row['currency_code']));
                if ($code === '' || $code === 'IQD') {
                    continue;
                }

                TenantRateOverride::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'currency_code' => $code,
                    ],
                    [
                        'buy_rate' => $row['buy_rate'],
                        'sell_rate' => $row['sell_rate'],
                    ],
                );
            }

            Log::info('admin.tenant.overrides_synced', [
                'tenant_id' => $tenant->id,
                'count' => count($rows),
                'by' => auth()->id(),
            ]);
        });
    }

    public function delete(Tenant $tenant): void
    {
        if ($tenant->is_primary) {
            throw ValidationException::withMessages([
                'tenant' => ['لا يمكن حذف حساب الموقع الأساسي.'],
            ]);
        }

        DB::transaction(function () use ($tenant) {
            $this->deleteLogoFile($tenant->logo_path);
            User::query()->where('tenant_id', $tenant->id)->update(['tenant_id' => null]);
            $tenantId = $tenant->id;
            $slug = $tenant->slug;
            $tenant->delete();

            Log::info('admin.tenant.deleted', [
                'tenant_id' => $tenantId,
                'slug' => $slug,
                'by' => auth()->id(),
            ]);
        });
    }

    private function normalizeSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name, '-');
        if ($base === '') {
            $base = 'office-'.Str::lower(Str::random(6));
        }

        $candidate = $base;
        $i = 2;
        while (
            Tenant::query()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function storeLogo(Tenant $tenant, UploadedFile $logo): string
    {
        return $logo->storeAs(
            'tenants/'.$tenant->id,
            'logo.'.$logo->getClientOriginalExtension() ?: 'png',
            'public',
        );
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
