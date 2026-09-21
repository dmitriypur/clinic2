<?php

namespace Tests\Feature;

use App\Enums\FrameGender;
use App\Models\Frame;
use App\Models\FrameAgeGroup;
use App\Models\FrameBrand;
use App\Models\FrameColor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FrameCatalogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_schema_contains_normalized_entities_and_default_age_groups(): void
    {
        $this->assertTrue(Schema::hasTable('frames'));
        $this->assertTrue(Schema::hasTable('frame_brands'));
        $this->assertTrue(Schema::hasTable('frame_colors'));
        $this->assertTrue(Schema::hasTable('frame_age_groups'));
        $this->assertTrue(Schema::hasTable('frame_color'));
        $this->assertTrue(Schema::hasTable('frame_age_group'));

        $this->assertSame(
            ['1-3', '3-7', '7-12', '12-18'],
            FrameAgeGroup::query()->orderBy('sort_order')->pluck('code')->all(),
        );
    }

    public function test_frame_brand_is_optional(): void
    {
        $brandColumn = collect(Schema::getColumns('frames'))->firstWhere('name', 'brand_id');

        $this->assertTrue($brandColumn['nullable']);
    }

    public function test_frame_persists_one_brand_and_multiple_colors_ages_and_genders(): void
    {
        $brand = FrameBrand::query()->create(['name' => 'Ray-Ban Kids']);
        $colors = collect([
            FrameColor::query()->create(['name' => 'Синий', 'hex' => '#1F3462']),
            FrameColor::query()->create(['name' => 'Розовый', 'hex' => '#EFAEB0']),
        ]);
        $ages = FrameAgeGroup::query()->orderBy('sort_order')->take(2)->get();

        $frame = Frame::query()->create([
            'brand_id' => $brand->getKey(),
            'model' => 'Junior 01',
            'description' => 'Лёгкая и прочная оправа',
            'genders' => ['girl', 'invalid', 'girl', 'boy'],
        ]);
        $colorIds = $colors->pluck('id')->all();
        $frame->colors()->sync($colorIds);
        $frame->ageGroups()->sync($ages->modelKeys());
        $frame->load(['brand', 'colors', 'ageGroups']);

        $this->assertTrue($frame->brand->is($brand));
        $this->assertEqualsCanonicalizing($colorIds, $frame->colors->modelKeys());
        $this->assertEqualsCanonicalizing($ages->modelKeys(), $frame->ageGroups->modelKeys());
        $this->assertSame(['girl', 'boy'], $frame->genders);
        $this->assertSame('Любой пол', $frame->genderLabel());
        $this->assertSame([
            FrameGender::BOY->value => 'Для мальчиков',
            FrameGender::GIRL->value => 'Для девочек',
        ], FrameGender::options());
    }

    public function test_public_catalog_excludes_inactive_frames_and_orders_by_priority_then_id(): void
    {
        $brand = FrameBrand::query()->create(['name' => 'Nano']);
        $second = $this->createFrame($brand, 'Second', 20);
        $firstOlder = $this->createFrame($brand, 'First older', 10);
        $firstNewer = $this->createFrame($brand, 'First newer', 10);
        $this->createFrame($brand, 'Hidden', 0, false);

        $this->assertSame(
            [$firstOlder->getKey(), $firstNewer->getKey(), $second->getKey()],
            Frame::query()->publicCatalog()->pluck('id')->all(),
        );
    }

    public function test_active_dictionary_scope_excludes_disabled_values_and_orders_them(): void
    {
        FrameColor::query()->create(['name' => 'Третий', 'hex' => '#333333', 'sort_order' => 30]);
        FrameColor::query()->create(['name' => 'Скрытый', 'hex' => '#222222', 'sort_order' => 1, 'is_active' => false]);
        FrameColor::query()->create(['name' => 'Первый', 'hex' => '#111111', 'sort_order' => 10]);

        $this->assertSame(
            ['Первый', 'Третий'],
            FrameColor::query()->activeOrdered()->pluck('name')->all(),
        );
    }

    public function test_in_use_brand_cannot_be_deleted(): void
    {
        $brand = FrameBrand::query()->create(['name' => 'Active brand']);
        $this->createFrame($brand, 'Model');

        try {
            $brand->delete();
            $this->fail('Deleting a brand used by a frame must violate the foreign key.');
        } catch (QueryException) {
            $this->assertDatabaseHas('frame_brands', ['id' => $brand->getKey()]);
        }
    }

    private function createFrame(
        FrameBrand $brand,
        string $model,
        int $sortOrder = 0,
        bool $isActive = true,
    ): Frame {
        return Frame::query()->create([
            'brand_id' => $brand->getKey(),
            'model' => $model,
            'genders' => [FrameGender::BOY->value],
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
    }
}
