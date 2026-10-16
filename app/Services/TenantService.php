<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TenantService
{
    public function primary(): Tenant
    {
        $tenant = Tenant::query()
            ->active()
            ->where('is_primary', true)
            ->first();

        if ($tenant) {
            return $tenant;
        }

        // Fallback when seeder has not run yet.
        return new Tenant([
            'name' => 'dinar-now',
            'slug' => 'dinar-now',
            'price_mode' => Tenant::PRICE_PANEL,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public function resolve(?string $slug): Tenant
    {
        $slug = $slug !== null ? trim($slug) : '';

        if ($slug === '') {
            return $this->primary();
        }

        $tenant = Tenant::query()
            ->active()
            ->where('slug', $slug)
            ->where('is_primary', false)
            ->first();

        if (! $tenant) {
            throw (new ModelNotFoundException)->setModel(Tenant::class, [$slug]);
        }

        return $tenant;
    }
}
