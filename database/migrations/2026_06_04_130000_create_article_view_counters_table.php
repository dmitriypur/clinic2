<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_view_counters')) {
            Schema::create('article_view_counters', function (Blueprint $table) {
                $table->id();
                $table->string('page_path', 1024);
                $table->char('page_path_hash', 64)->unique();
                $table->string('handle')->index();
                $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
                $table->unsignedBigInteger('views_count')->default(0);
                $table->string('source', 32)->default('local');
                $table->timestamps();

                $table->index(['page_id', 'views_count']);
            });

            return;
        }

        Schema::table('article_view_counters', function (Blueprint $table) {
            if (! Schema::hasColumn('article_view_counters', 'page_path_hash')) {
                $table->char('page_path_hash', 64)->nullable()->after('page_path')->unique();
            }
        });

        DB::table('article_view_counters')
            ->whereNull('page_path_hash')
            ->orderBy('id')
            ->eachById(function ($counter): void {
                DB::table('article_view_counters')
                    ->where('id', $counter->id)
                    ->update([
                        'page_path_hash' => hash('sha256', (string) $counter->page_path),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_view_counters');
    }
};
