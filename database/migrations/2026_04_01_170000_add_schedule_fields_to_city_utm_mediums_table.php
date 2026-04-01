<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('city_utm_mediums')) {
            return;
        }

        Schema::table('city_utm_mediums', function (Blueprint $table) {
            if (! Schema::hasColumn('city_utm_mediums', 'start_date')) {
                $table->date('start_date')->nullable();
            }

            if (! Schema::hasColumn('city_utm_mediums', 'end_date')) {
                $table->date('end_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('city_utm_mediums')) {
            return;
        }

        Schema::table('city_utm_mediums', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('city_utm_mediums', 'start_date')) {
                $columns[] = 'start_date';
            }

            if (Schema::hasColumn('city_utm_mediums', 'end_date')) {
                $columns[] = 'end_date';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
