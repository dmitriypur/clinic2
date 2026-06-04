<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_booking_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->uuid('event_uuid')->unique();
            $table->string('page_url', 2048);
            $table->string('page_path', 1024);
            $table->string('entry_point', 64)->default('booking_widget');
            $table->string('booking_mode', 32)->nullable();
            $table->timestamps();

            $table->index(['page_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_booking_conversions');
    }
};
