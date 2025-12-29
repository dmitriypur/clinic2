<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pages <-> Cities
        if (!Schema::hasTable('city_page')) {
            Schema::create('city_page', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->foreignId('page_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['city_id', 'page_id']);
            });
        }

        // Doctors <-> Cities
        if (!Schema::hasTable('city_doctor')) {
            Schema::create('city_doctor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['city_id', 'doctor_id']);
            });
        }

        // Services <-> Cities
        if (!Schema::hasTable('city_service')) {
            Schema::create('city_service', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['city_id', 'service_id']);
            });
        }

        // Promotions <-> Cities
        if (!Schema::hasTable('city_promotion')) {
            Schema::create('city_promotion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['city_id', 'promotion_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('city_page');
        Schema::dropIfExists('city_doctor');
        Schema::dropIfExists('city_service');
        Schema::dropIfExists('city_promotion');
    }
};
