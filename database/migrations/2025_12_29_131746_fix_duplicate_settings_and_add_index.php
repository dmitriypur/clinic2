<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find duplicates
        $duplicates = DB::table('settings')
            ->select('group', 'name', DB::raw('count(*) as total'))
            ->groupBy('group', 'name')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // 2. Get all IDs for this group/name, ordered by id DESC (keep latest)
            // We assume higher ID is the most recent "save" attempt
            $ids = DB::table('settings')
                ->where('group', $duplicate->group)
                ->where('name', $duplicate->name)
                ->orderBy('id', 'desc')
                ->pluck('id')
                ->toArray();

            // Keep the first one ($ids[0]), delete the rest
            $idsToDelete = array_slice($ids, 1);

            if (!empty($idsToDelete)) {
                DB::table('settings')->whereIn('id', $idsToDelete)->delete();
            }
        }

        // 3. Add unique index to prevent future duplicates
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['group', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['group', 'name']);
        });
    }
};
