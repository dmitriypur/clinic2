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
        if (Schema::hasTable('page_tag')) return;
        Schema::create('page_tag', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages');
            $table->foreignId('tag_id')->constrained('tags');
            $table->primary(['page_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_tag');
    }
};
