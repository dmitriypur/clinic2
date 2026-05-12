<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE city_utm_campaigns MODIFY started_at TIMESTAMP NULL');
        }
    }

    public function down(): void
    {
        DB::table('city_utm_campaigns')
            ->whereNull('started_at')
            ->update(['started_at' => DB::raw('created_at')]);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE city_utm_campaigns MODIFY started_at TIMESTAMP NOT NULL');
        }
    }
};
