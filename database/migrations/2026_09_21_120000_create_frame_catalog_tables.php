<?php

use App\Enums\BlockType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frame_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('frame_colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->char('hex', 7)->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('frame_age_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('frames', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained('frame_brands')->restrictOnDelete();
            $table->foreignId('curator_media_id')->nullable()->constrained('curator_media')->restrictOnDelete();
            $table->string('model');
            $table->string('description')->nullable();
            $table->json('genders');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_hit')->default(false);
            $table->boolean('is_school_choice')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order', 'id']);
        });

        Schema::create('frame_color', function (Blueprint $table): void {
            $table->foreignId('frame_id')->constrained('frames')->cascadeOnDelete();
            $table->foreignId('frame_color_id')->constrained('frame_colors')->restrictOnDelete();
            $table->primary(['frame_id', 'frame_color_id']);
        });

        Schema::create('frame_age_group', function (Blueprint $table): void {
            $table->foreignId('frame_id')->constrained('frames')->cascadeOnDelete();
            $table->foreignId('frame_age_group_id')->constrained('frame_age_groups')->restrictOnDelete();
            $table->primary(['frame_id', 'frame_age_group_id']);
        });

        $this->seedAgeGroups();
        $this->migrateLegacyCards();
    }

    public function down(): void
    {
        Schema::dropIfExists('frame_age_group');
        Schema::dropIfExists('frame_color');
        Schema::dropIfExists('frames');
        Schema::dropIfExists('frame_age_groups');
        Schema::dropIfExists('frame_colors');
        Schema::dropIfExists('frame_brands');
    }

    private function seedAgeGroups(): void
    {
        $now = now();

        DB::table('frame_age_groups')->insert([
            ['name' => '1–3 года', 'code' => '1-3', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '3–7 лет', 'code' => '3-7', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '7–12 лет', 'code' => '7-12', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '12–18 лет', 'code' => '12-18', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function migrateLegacyCards(): void
    {
        if (! Schema::hasTable('blocks')) {
            return;
        }

        $blocks = DB::table('blocks')
            ->where('type', BlockType::KIDS_OPTICS_FRAME_CATALOG->value)
            ->get(['payload']);

        $items = $blocks
            ->flatMap(function ($block): array {
                $payload = json_decode((string) $block->payload, true);

                return is_array($payload['items'] ?? null) ? $payload['items'] : [];
            })
            ->filter(fn ($item): bool => is_array($item))
            ->values();

        if ($items->isEmpty()) {
            return;
        }

        $now = now();
        $brandId = DB::table('frame_brands')->insertGetId([
            'name' => 'Без бренда',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $ageIds = DB::table('frame_age_groups')->pluck('id', 'code');
        $knownMediaIds = DB::table('curator_media')->pluck('id')->mapWithKeys(
            static fn ($id): array => [(int) $id => true],
        );

        foreach ($items as $index => $item) {
            $mediaId = (int) ($item['curator_media_id'] ?? 0);
            $gender = $item['gender'] ?? 'unisex';
            $genders = match ($gender) {
                'boy' => ['boy'],
                'girl' => ['girl'],
                default => ['boy', 'girl'],
            };

            $frameId = DB::table('frames')->insertGetId([
                'brand_id' => $brandId,
                'curator_media_id' => $knownMediaIds->has($mediaId) ? $mediaId : null,
                'model' => trim((string) ($item['title'] ?? '')) ?: 'Модель '.($index + 1),
                'description' => trim((string) ($item['description'] ?? '')) ?: null,
                'genders' => json_encode($genders, JSON_THROW_ON_ERROR),
                'sort_order' => ($index + 1) * 10,
                'is_hit' => false,
                'is_school_choice' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $ageId = $ageIds->get((string) ($item['age_group'] ?? ''));
            if ($ageId !== null) {
                DB::table('frame_age_group')->insert([
                    'frame_id' => $frameId,
                    'frame_age_group_id' => $ageId,
                ]);
            }

            foreach (['color_1', 'color_2', 'color_3'] as $colorKey) {
                $hex = strtoupper((string) ($item[$colorKey] ?? ''));
                if (preg_match('/^#[0-9A-F]{6}$/', $hex) !== 1) {
                    continue;
                }

                $colorId = DB::table('frame_colors')->where('hex', $hex)->value('id');
                if ($colorId === null) {
                    $colorId = DB::table('frame_colors')->insertGetId([
                        'name' => "Цвет {$hex}",
                        'hex' => $hex,
                        'sort_order' => DB::table('frame_colors')->count() * 10 + 10,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('frame_color')->insertOrIgnore([
                    'frame_id' => $frameId,
                    'frame_color_id' => $colorId,
                ]);
            }
        }
    }
};
