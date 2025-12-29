<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('services')->nullOnDelete();
            }
            if (!Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('services', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });

        if (!Schema::hasTable('service_prices')) {
            Schema::create('service_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->cascadeOnDelete();
                $table->integer('price');
                $table->boolean('price_from')->default(false);
                $table->timestamps();

                $table->unique(['service_id', 'city_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_prices');

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_active', 'sort_order']);
        });
    }
};
