<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('price_mode', 20)->default('panel'); // panel | custom
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_primary']);
        });

        Schema::create('tenant_rate_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('currency_code', 10);
            $table->decimal('buy_rate', 18, 4);
            $table->decimal('sell_rate', 18, 4);
            $table->timestamps();

            $table->unique(['tenant_id', 'currency_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('platform_admin')->after('password');
            $table->foreignId('tenant_id')->nullable()->after('role')->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('tenant_rate_overrides');
        Schema::dropIfExists('tenants');
    }
};
