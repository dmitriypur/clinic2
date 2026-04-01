<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_utm_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->timestamps();

            $table->unique(['city_id', 'phone']);
        });

        Schema::create('city_utm_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('name')->nullable();
            $table->foreignId('default_phone_id')->nullable()->constrained('city_utm_phones')->nullOnDelete();
            $table->timestamps();

            $table->unique(['city_id', 'source']);
        });

        Schema::create('city_utm_mediums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('city_utm_sources')->cascadeOnDelete();
            $table->string('medium');
            $table->string('medium_name')->nullable();
            $table->foreignId('phone_id')->nullable()->constrained('city_utm_phones')->nullOnDelete();
            $table->timestamps();

            $table->unique(['city_id', 'source_id', 'medium']);
        });

        $this->backfillLegacyCityUtmPhones();
    }

    public function down(): void
    {
        Schema::dropIfExists('city_utm_mediums');
        Schema::dropIfExists('city_utm_sources');
        Schema::dropIfExists('city_utm_phones');
    }

    private function backfillLegacyCityUtmPhones(): void
    {
        $now = now();

        DB::table('cities')
            ->select(['id', 'utm_phones'])
            ->orderBy('id')
            ->chunkById(100, function ($cities) use ($now) {
                foreach ($cities as $city) {
                    $legacyRules = $city->utm_phones;

                    if (is_string($legacyRules)) {
                        $legacyRules = json_decode($legacyRules, true);
                    }

                    if (! is_array($legacyRules) || $legacyRules === []) {
                        continue;
                    }

                    $phoneIdsByNumber = [];

                    foreach ($legacyRules as $legacyRule) {
                        $source = trim((string) data_get($legacyRule, 'source', ''));

                        if ($source === '') {
                            continue;
                        }

                        $defaultPhoneId = $this->resolvePhoneId(
                            cityId: (int) $city->id,
                            phone: data_get($legacyRule, 'phone'),
                            phoneIdsByNumber: $phoneIdsByNumber,
                            now: $now,
                        );

                        $existingSourceId = DB::table('city_utm_sources')
                            ->where('city_id', $city->id)
                            ->where('source', $source)
                            ->value('id');

                        if ($existingSourceId) {
                            DB::table('city_utm_sources')
                                ->where('id', $existingSourceId)
                                ->update([
                                    'default_phone_id' => $defaultPhoneId,
                                    'updated_at' => $now,
                                ]);

                            $sourceId = (int) $existingSourceId;
                        } else {
                            $sourceId = (int) DB::table('city_utm_sources')->insertGetId([
                                'city_id' => $city->id,
                                'source' => $source,
                                'name' => null,
                                'default_phone_id' => $defaultPhoneId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }

                        foreach ((array) data_get($legacyRule, 'medium', []) as $legacyMedium) {
                            $medium = trim((string) data_get($legacyMedium, 'name', ''));

                            if ($medium === '') {
                                continue;
                            }

                            $phoneId = $this->resolvePhoneId(
                                cityId: (int) $city->id,
                                phone: data_get($legacyMedium, 'phone'),
                                phoneIdsByNumber: $phoneIdsByNumber,
                                now: $now,
                            );

                            $existingMediumId = DB::table('city_utm_mediums')
                                ->where('city_id', $city->id)
                                ->where('source_id', $sourceId)
                                ->where('medium', $medium)
                                ->value('id');

                            if ($existingMediumId) {
                                DB::table('city_utm_mediums')
                                    ->where('id', $existingMediumId)
                                    ->update([
                                        'phone_id' => $phoneId,
                                        'updated_at' => $now,
                                    ]);

                                continue;
                            }

                            DB::table('city_utm_mediums')->insert([
                                'city_id' => $city->id,
                                'source_id' => $sourceId,
                                'medium' => $medium,
                                'medium_name' => null,
                                'phone_id' => $phoneId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            });
    }

    private function resolvePhoneId(int $cityId, mixed $phone, array &$phoneIdsByNumber, $now): ?int
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        if (isset($phoneIdsByNumber[$phone])) {
            return $phoneIdsByNumber[$phone];
        }

        $existingPhoneId = DB::table('city_utm_phones')
            ->where('city_id', $cityId)
            ->where('phone', $phone)
            ->value('id');

        if ($existingPhoneId) {
            return $phoneIdsByNumber[$phone] = (int) $existingPhoneId;
        }

        $phoneIdsByNumber[$phone] = (int) DB::table('city_utm_phones')->insertGetId([
            'city_id' => $cityId,
            'phone' => $phone,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $phoneIdsByNumber[$phone];
    }
};
