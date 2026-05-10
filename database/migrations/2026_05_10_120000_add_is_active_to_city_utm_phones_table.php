<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_phones', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('phone');
        });

        DB::table('city_utm_phones')->update(['is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('city_utm_phones', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
