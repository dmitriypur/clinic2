<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->index(
                ['category_id', 'type', 'active', 'created_at', 'id'],
                'idx_pages_article_navigation_order',
            );
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropIndex('idx_pages_article_navigation_order');
        });
    }
};
