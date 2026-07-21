<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_search_queries', function (Blueprint $table): void {
            $table->id();
            $table->string('query', 100);
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('results_count');
            $table->timestamps();

            $table->index('created_at');
            $table->index(['city_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_search_queries');
    }
};
