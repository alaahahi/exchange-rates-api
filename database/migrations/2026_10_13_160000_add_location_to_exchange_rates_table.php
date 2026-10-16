<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('location', 32)->default('erbil')->after('currency_code');
        });

        DB::table('exchange_rates')->whereNull('location')->orWhere('location', '')->update([
            'location' => 'erbil',
        ]);

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique(['currency_code']);
        });

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->unique(['currency_code', 'location']);
            $table->index(['location', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique(['currency_code', 'location']);
            $table->dropIndex(['location', 'is_active', 'sort_order']);
        });

        // Keep only default location rows before restoring currency uniqueness.
        $default = 'erbil';
        $keepIds = DB::table('exchange_rates')
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('currency_code')
            ->pluck('id');

        DB::table('exchange_rates')
            ->where('location', '!=', $default)
            ->delete();

        DB::table('exchange_rates')
            ->whereNotIn('id', $keepIds)
            ->delete();

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->unique(['currency_code']);
        });
    }
};
