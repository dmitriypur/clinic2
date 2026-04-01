<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('cities') ||
            ! Schema::hasTable('city_utm_sources') ||
            ! Schema::hasTable('city_utm_mediums') ||
            ! Schema::hasTable('city_utm_phones')
        ) {
            return;
        }

        $now = now();
        $today = $now->toDateString();

        DB::table('cities')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($cities) use ($now, $today): void {
                foreach ($cities as $city) {
                    $mediumPhoneIds = DB::table('city_utm_mediums')
                        ->where('city_id', $city->id)
                        ->whereNotNull('phone_id')
                        ->pluck('phone_id')
                        ->filter()
                        ->unique()
                        ->all();

                    if ($mediumPhoneIds !== []) {
                        DB::table('city_utm_sources')
                            ->where('city_id', $city->id)
                            ->whereIn('default_phone_id', $mediumPhoneIds)
                            ->update([
                                'default_phone_id' => null,
                                'updated_at' => $now,
                            ]);
                    }

                    $legacyRules = [];
                    $sources = DB::table('city_utm_sources as sources')
                        ->leftJoin('city_utm_phones as phones', 'phones.id', '=', 'sources.default_phone_id')
                        ->where('sources.city_id', $city->id)
                        ->orderBy('sources.source')
                        ->get([
                            'sources.id',
                            'sources.source',
                            'phones.phone as default_phone',
                        ]);

                    foreach ($sources as $source) {
                        $mediums = DB::table('city_utm_mediums as mediums')
                            ->leftJoin('city_utm_phones as phones', 'phones.id', '=', 'mediums.phone_id')
                            ->where('mediums.city_id', $city->id)
                            ->where('mediums.source_id', $source->id)
                            ->where(function ($query) use ($today): void {
                                $query->whereNull('mediums.start_date')
                                    ->orWhereDate('mediums.start_date', '<=', $today);
                            })
                            ->where(function ($query) use ($today): void {
                                $query->whereNull('mediums.end_date')
                                    ->orWhereDate('mediums.end_date', '>', $today);
                            })
                            ->orderBy('mediums.medium')
                            ->get([
                                'mediums.medium',
                                'phones.phone',
                            ])
                            ->map(fn ($medium): array => [
                                'name' => $medium->medium,
                                'phone' => $medium->phone,
                            ])
                            ->all();

                        $legacyRules[] = [
                            'source' => $source->source,
                            'phone' => $source->default_phone,
                            'medium' => $mediums,
                        ];
                    }

                    DB::table('cities')
                        ->where('id', $city->id)
                        ->update([
                            'utm_phones' => json_encode($legacyRules, JSON_UNESCAPED_UNICODE),
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data cleanup migration: intentionally irreversible.
    }
};
