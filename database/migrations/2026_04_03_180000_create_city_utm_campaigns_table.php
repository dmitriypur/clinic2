<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_utm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('city_utm_sources')->cascadeOnDelete();
            $table->string('medium')->nullable();
            $table->string('medium_name')->nullable();
            $table->foreignId('phone_id')->nullable()->constrained('city_utm_phones')->nullOnDelete();
            $table->boolean('open_booking_widget')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('restarted_from_id')->nullable()->constrained('city_utm_campaigns')->nullOnDelete();
            $table->timestamps();

            $table->index(['city_id', 'archived_at']);
            $table->index(['city_id', 'source_id', 'medium']);
        });

        $this->backfillCampaigns();
    }

    public function down(): void
    {
        Schema::dropIfExists('city_utm_campaigns');
    }

    private function backfillCampaigns(): void
    {
        $now = now();
        $today = Carbon::today();
        $activePhoneUsage = [];

        DB::table('city_utm_mediums')
            ->orderBy('id')
            ->chunkById(100, function ($mediums) use ($now, $today, &$activePhoneUsage): void {
                foreach ($mediums as $medium) {
                    $startedAt = $this->resolveStartedAt($medium->start_date, $medium->created_at, $now);
                    [$stoppedAt, $archivedAt] = $this->resolveStopState($medium->end_date, $today);

                    DB::table('city_utm_campaigns')->insert([
                        'city_id' => $medium->city_id,
                        'source_id' => $medium->source_id,
                        'medium' => $medium->medium,
                        'medium_name' => $medium->medium_name,
                        'phone_id' => $medium->phone_id,
                        'open_booking_widget' => (bool) ($medium->open_booking_widget ?? false),
                        'started_at' => $startedAt,
                        'stopped_at' => $stoppedAt,
                        'archived_at' => $archivedAt,
                        'restarted_from_id' => null,
                        'created_at' => $medium->created_at ?? $now,
                        'updated_at' => $medium->updated_at ?? $now,
                    ]);

                    if (! $archivedAt && $medium->phone_id) {
                        $activePhoneUsage[$medium->city_id][$medium->phone_id] = true;
                    }
                }
            });

        DB::table('city_utm_sources')
            ->orderBy('id')
            ->chunkById(100, function ($sources) use ($now, &$activePhoneUsage): void {
                foreach ($sources as $source) {
                    if (! $source->default_phone_id && ! $source->open_booking_widget) {
                        continue;
                    }

                    $phoneId = $source->default_phone_id;

                    if (
                        $phoneId &&
                        (($activePhoneUsage[$source->city_id][$phoneId] ?? false) === true)
                    ) {
                        $phoneId = null;
                    }

                    if ($phoneId) {
                        $activePhoneUsage[$source->city_id][$phoneId] = true;
                    }

                    DB::table('city_utm_campaigns')->insert([
                        'city_id' => $source->city_id,
                        'source_id' => $source->id,
                        'medium' => null,
                        'medium_name' => null,
                        'phone_id' => $phoneId,
                        'open_booking_widget' => (bool) ($source->open_booking_widget ?? false),
                        'started_at' => $source->created_at ?? $now,
                        'stopped_at' => null,
                        'archived_at' => null,
                        'restarted_from_id' => null,
                        'created_at' => $source->created_at ?? $now,
                        'updated_at' => $source->updated_at ?? $now,
                    ]);
                }
            });
    }

    private function resolveStartedAt(?string $startDate, mixed $fallback, Carbon $now): Carbon
    {
        if ($startDate) {
            return Carbon::parse($startDate)->startOfDay();
        }

        return $fallback ? Carbon::parse($fallback) : $now;
    }

    private function resolveStopState(?string $endDate, Carbon $today): array
    {
        if (! $endDate) {
            return [null, null];
        }

        $end = Carbon::parse($endDate)->endOfDay();

        if ($end->lt($today->copy()->startOfDay())) {
            return [$end, $end];
        }

        if ($end->equalTo($today->copy()->endOfDay())) {
            return [$end, $end];
        }

        return [null, null];
    }
};
