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
        // Добавляем индексы для таблицы pages
        Schema::table('pages', function (Blueprint $table) {
            $table->index('active', 'idx_pages_active');
            $table->index('type', 'idx_pages_type');
            $table->index(['active', 'type'], 'idx_pages_active_type');
        });

        // Добавляем индексы для таблицы blocks
        Schema::table('blocks', function (Blueprint $table) {
            $table->index(['page_id', 'type'], 'idx_blocks_page_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('idx_pages_active');
            $table->dropIndex('idx_pages_type');
            $table->dropIndex('idx_pages_active_type');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->dropIndex('idx_blocks_page_type');
        });
    }
};
