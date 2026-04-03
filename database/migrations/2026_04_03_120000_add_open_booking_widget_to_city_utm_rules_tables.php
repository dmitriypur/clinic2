<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_sources', function (Blueprint $table): void {
            $table->boolean('open_booking_widget')->default(false)->after('default_phone_id');
        });

        Schema::table('city_utm_mediums', function (Blueprint $table): void {
            $table->boolean('open_booking_widget')->default(false)->after('phone_id');
        });
    }

    public function down(): void
    {
        Schema::table('city_utm_mediums', function (Blueprint $table): void {
            $table->dropColumn('open_booking_widget');
        });

        Schema::table('city_utm_sources', function (Blueprint $table): void {
            $table->dropColumn('open_booking_widget');
        });
    }
};
