<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->string('campaign')->nullable()->after('medium_name');
            $table->string('campaign_name')->nullable()->after('campaign');
            $table->index(['city_id', 'source_id', 'medium', 'campaign'], 'city_utm_campaigns_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('city_utm_campaigns', function (Blueprint $table): void {
            $table->dropIndex('city_utm_campaigns_lookup_index');
            $table->dropColumn(['campaign', 'campaign_name']);
        });
    }
};
