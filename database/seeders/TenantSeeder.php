<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['slug' => 'dinar-now'],
            [
                'name' => 'dinar-now',
                'price_mode' => Tenant::PRICE_PANEL,
                'is_primary' => true,
                'is_active' => true,
                'logo_path' => null,
            ],
        );

        User::query()
            ->whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => User::ROLE_PLATFORM_ADMIN]);

        User::query()
            ->where('email', 'admin@dinar.local')
            ->update([
                'role' => User::ROLE_PLATFORM_ADMIN,
                'tenant_id' => null,
            ]);
    }
}
