<?php

namespace Tests\Feature;

use App\Enums\FrameGender;
use App\Models\Frame;
use App\Models\FrameAgeGroup;
use App\Models\FrameBrand;
use App\Models\FrameColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrameCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_combines_age_and_gender_filters_and_returns_rendered_page(): void
    {
        [$young, $school] = FrameAgeGroup::query()->orderBy('sort_order')->take(2)->get();
        $brand = FrameBrand::query()->create(['name' => 'Nano']);

        $boy = $this->createFrame($brand, 'Boy', ['boy'], [$young->id], 10);
        $girl = $this->createFrame($brand, 'Girl', ['girl'], [$young->id], 20);
        $olderGirl = $this->createFrame($brand, 'Older girl', ['girl'], [$school->id], 30);
        $unisex = $this->createFrame($brand, 'Unisex', ['boy', 'girl'], [$young->id], 40);
        $this->createFrame($brand, 'Hidden', ['girl'], [$young->id], 0, false);

        $response = $this->getJson('/api/frame-catalog?'.http_build_query([
            'ages' => [$young->id],
            'genders' => [FrameGender::GIRL->value],
            'offset' => 0,
            'limit' => 2,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('hasMore', false)
            ->assertJsonPath('nextOffset', 2);

        $html = $response->json('html');
        $this->assertStringContainsString('Nano Girl', $html);
        $this->assertStringContainsString('Nano Unisex', $html);
        $this->assertStringNotContainsString('Nano Boy', $html);
        $this->assertStringNotContainsString('Nano Older girl', $html);
    }

    public function test_multiple_ages_use_or_and_both_genders_remove_gender_restriction(): void
    {
        [$young, $school] = FrameAgeGroup::query()->orderBy('sort_order')->take(2)->get();
        $brand = FrameBrand::query()->create(['name' => 'Flex']);

        $this->createFrame($brand, 'Young boy', ['boy'], [$young->id], 10);
        $this->createFrame($brand, 'School girl', ['girl'], [$school->id], 20);
        $this->createFrame($brand, 'Other age', ['boy'], [FrameAgeGroup::query()->orderBy('sort_order')->skip(2)->value('id')], 30);

        $response = $this->getJson('/api/frame-catalog?'.http_build_query([
            'ages' => [$young->id, $school->id],
            'genders' => ['boy', 'girl'],
            'limit' => 12,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('total', 2);

        $html = $response->json('html');
        $this->assertStringContainsString('Flex Young boy', $html);
        $this->assertStringContainsString('Flex School girl', $html);
        $this->assertStringNotContainsString('Flex Other age', $html);
    }

    public function test_api_returns_an_unbranded_frame_with_its_model_as_the_title(): void
    {
        $age = FrameAgeGroup::query()->orderBy('sort_order')->firstOrFail();
        $frame = Frame::query()->create([
            'model' => 'Model without brand',
            'description' => 'Описание',
            'genders' => ['girl'],
        ]);
        $frame->ageGroups()->attach($age);

        $response = $this->getJson('/api/frame-catalog?limit=12');

        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('Model without brand', $response->json('html'));
    }

    public function test_api_rejects_oversized_pages_and_unknown_filter_values(): void
    {
        $this->getJson('/api/frame-catalog?limit=13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');

        $this->getJson('/api/frame-catalog?ages[]=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ages.0');

        $this->getJson('/api/frame-catalog?genders[]=other')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('genders.0');
    }

    public function test_disabled_dictionary_values_do_not_leak_into_public_results(): void
    {
        $age = FrameAgeGroup::query()->orderBy('sort_order')->firstOrFail();
        $activeBrand = FrameBrand::query()->create(['name' => 'Visible']);
        $disabledBrand = FrameBrand::query()->create(['name' => 'Hidden brand', 'is_active' => false]);
        $activeColor = FrameColor::query()->create(['name' => 'Синий', 'hex' => '#123456']);
        $disabledColor = FrameColor::query()->create(['name' => 'Скрытый', 'hex' => '#ABCDEF', 'is_active' => false]);
        $visible = $this->createFrame($activeBrand, 'Model', ['boy'], [$age->id]);
        $visible->colors()->sync([$activeColor->id, $disabledColor->id]);
        $this->createFrame($disabledBrand, 'Private', ['boy'], [$age->id]);

        $response = $this->getJson('/api/frame-catalog?limit=12');

        $response->assertOk()->assertJsonPath('total', 1);
        $html = $response->json('html');
        $this->assertStringContainsString('#123456', $html);
        $this->assertStringNotContainsString('#ABCDEF', $html);
        $this->assertStringNotContainsString('Hidden brand Private', $html);
    }

    private function createFrame(
        FrameBrand $brand,
        string $model,
        array $genders,
        array $ageIds,
        int $sortOrder = 0,
        bool $isActive = true,
    ): Frame {
        $frame = Frame::query()->create([
            'brand_id' => $brand->id,
            'model' => $model,
            'description' => 'Описание',
            'genders' => $genders,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);
        $frame->ageGroups()->sync($ageIds);

        return $frame;
    }
}
