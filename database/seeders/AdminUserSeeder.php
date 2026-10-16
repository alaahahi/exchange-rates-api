<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@dinar.local'],
            [
                'name' => 'مدير النظام',
                'password' => 'password',
                'role' => User::ROLE_PLATFORM_ADMIN,
                'tenant_id' => null,
                'email_verified_at' => now(),
            ],
        );
    }
}
