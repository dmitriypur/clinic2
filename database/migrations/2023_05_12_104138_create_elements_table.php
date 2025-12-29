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
        Schema::create('elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')
                ->index()
                ->constrained('blocks')
                ->cascadeOnDelete();

            $table->uuid()->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description_html')->nullable();
            $table->unsignedTinyInteger('has_price')->default(0)->index();
            $table->unsignedTinyInteger('has_an_appointment')->default(0);
            $table->integer('order_column')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elements');
    }
};
