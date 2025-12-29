<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_default')->default(false);
                $table->boolean('active')->default(true);
                $table->json('contacts')->nullable(); // phone, address, coords, etc.
                $table->json('seo_cases')->nullable(); // prepositional, genitive cases
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
