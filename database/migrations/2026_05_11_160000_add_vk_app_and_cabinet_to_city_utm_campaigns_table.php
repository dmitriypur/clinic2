<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->string('cabinet')->nullable()->after('open_booking_widget');
            $table->boolean('vk_app_enabled')->default(false)->after('cabinet');
        });
    }

    public function down(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['cabinet', 'vk_app_enabled']);
        });
    }
};
