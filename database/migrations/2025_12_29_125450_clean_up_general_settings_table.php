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
        // Spatie Laravel Settings uses a 'settings' table with 'group', 'name', 'payload' columns.
        // It does NOT use individual columns for each setting.
        // Therefore, we do not need to drop columns from the table.
        // Instead, we need to remove the obsolete rows from the settings table where group = 'general'.
        
        // List of keys to remove
        $keysToRemove = [
            'phone',
            'vk',
            'telegram',
            'youtube',
            'email',
            'city',
            'postal_code',
            'address',
            'coordinates',
            'schedule',
            'special_schedule',
            'special_schedule_title',
            'show_special_schedule',
            'rutube',
            'vk_video',
            'utm',
            'metro',
        ];

        foreach ($keysToRemove as $key) {
            \DB::table('settings')
                ->where('group', 'general')
                ->where('name', $key)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily restore the deleted rows without a backup.
        // This migration is destructive for these specific keys.
    }
};
