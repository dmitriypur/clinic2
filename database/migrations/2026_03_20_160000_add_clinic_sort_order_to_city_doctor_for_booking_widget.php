<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_doctor', function (Blueprint $table): void {
            if (! Schema::hasColumn('city_doctor', 'clinic_sort_order')) {
                $table->integer('clinic_sort_order')->nullable()->after('sort_order');
                $table->index(['city_id', 'clinic_sort_order'], 'city_doctor_city_clinic_sort_order_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('city_doctor', function (Blueprint $table): void {
            if (Schema::hasColumn('city_doctor', 'clinic_sort_order')) {
                $table->dropIndex('city_doctor_city_clinic_sort_order_index');
                $table->dropColumn('clinic_sort_order');
            }
        });
    }
};
