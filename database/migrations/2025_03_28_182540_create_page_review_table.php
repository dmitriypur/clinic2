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
        if (Schema::hasTable('page_review')) return;
        Schema::create('page_review', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages');
            $table->foreignId('review_id')->constrained('reviews');
            $table->primary(['page_id', 'review_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_review');
    }
};
