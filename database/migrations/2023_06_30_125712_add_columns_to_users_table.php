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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('uuid')->nullable();
            $table->string('middle_name')->after('name')->nullable();
            $table->string('birthday')->after('middle_name')->nullable();
            $table->unsignedTinyInteger('accept_sms_notifications')->after('email_verified_at')->default(0);
            $table->unsignedTinyInteger('accept_sms_promotions')->after('accept_sms_notifications')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_name',
                'middle_name',
                'birthday',
                'accept_sms_notifications',
                'accept_sms_promotions',
            ]);
        });
    }
};
