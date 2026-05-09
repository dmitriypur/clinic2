<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->boolean('is_organic_overridden')->default(false)->after('is_organic');
        });
    }

    public function down(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->dropColumn('is_organic_overridden');
        });
    }
};
