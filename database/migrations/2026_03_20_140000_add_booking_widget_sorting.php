<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_doctor', function (Blueprint $table): void {
            if (! Schema::hasColumn('city_doctor', 'sort_order')) {
                $table->integer('sort_order')->nullable()->after('doctor_id');
                $table->index(['city_id', 'sort_order'], 'city_doctor_city_sort_order_index');
            }
        });

        if (! Schema::hasTable('booking_widget_branch_orders')) {
            Schema::create('booking_widget_branch_orders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('clinic_id');
                $table->string('clinic_name')->nullable();
                $table->unsignedInteger('branch_id');
                $table->string('title');
                $table->integer('sort_order')->nullable();
                $table->timestamps();

                $table->unique(
                    ['city_id', 'clinic_id', 'branch_id'],
                    'booking_widget_branch_orders_city_clinic_branch_unique'
                );
                $table->index(
                    ['city_id', 'clinic_id', 'sort_order'],
                    'booking_widget_branch_orders_city_clinic_sort_order_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_widget_branch_orders');

        Schema::table('city_doctor', function (Blueprint $table): void {
            if (Schema::hasColumn('city_doctor', 'sort_order')) {
                $table->dropIndex('city_doctor_city_sort_order_index');
                $table->dropColumn('sort_order');
            }
        });
    }
};
