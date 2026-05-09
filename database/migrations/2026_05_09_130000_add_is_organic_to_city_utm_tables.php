<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_sources', function (Blueprint $table): void {
            $table->boolean('is_organic')->default(false)->after('open_booking_widget');
        });

        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->boolean('is_organic')->default(false)->after('open_booking_widget');
        });
    }

    public function down(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->dropColumn('is_organic');
        });

        Schema::table('city_utm_sources', function (Blueprint $table): void {
            $table->dropColumn('is_organic');
        });
    }
};
