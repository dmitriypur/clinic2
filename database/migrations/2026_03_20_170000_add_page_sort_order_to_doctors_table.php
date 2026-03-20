<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            if (! Schema::hasColumn('doctors', 'page_sort_order')) {
                $table->integer('page_sort_order')->nullable()->after('job_title');
                $table->index('page_sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            if (Schema::hasColumn('doctors', 'page_sort_order')) {
                $table->dropIndex(['page_sort_order']);
                $table->dropColumn('page_sort_order');
            }
        });
    }
};
