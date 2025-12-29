<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('contacts');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('coordinates')->nullable();
            $table->string('schedule')->nullable();
            $table->string('metro')->nullable();
            $table->json('social_links')->nullable();
            $table->json('utm_phones')->nullable();
            $table->text('special_schedule')->nullable();
            $table->boolean('show_special_schedule')->default(false);
            $table->string('special_schedule_title')->nullable();
            $table->json('header_scripts')->nullable();
            $table->json('body_scripts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->json('contacts')->nullable();
            $table->dropColumn([
                'phone',
                'email',
                'address',
                'postal_code',
                'coordinates',
                'schedule',
                'metro',
                'social_links',
                'utm_phones',
                'special_schedule',
                'show_special_schedule',
                'special_schedule_title',
                'header_scripts',
                'body_scripts',
            ]);
        });
    }
};
