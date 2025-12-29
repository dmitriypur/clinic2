<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Page::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('type');
            $table->string('title');
            $table->string('anchor')->nullable();
            $table->longText('body_html')->nullable();
            $table->integer('order_column')->index();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
