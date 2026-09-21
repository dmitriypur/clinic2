<?php

namespace Tests\Feature;

use App\Enums\FrameGender;
use App\Models\Frame;
use App\Models\FrameAgeGroup;
use App\Models\FrameBrand;
use App\Services\FrameCatalogService;
use App\Blocks\BlockRegistry;
use App\Models\Block;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrameSchoolSliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_slider_prioritizes_school_choice_then_hits_then_other_frames(): void
    {
        $brand = FrameBrand::query()->create(['name' => 'Nano']);
        $age = FrameAgeGroup::query()->orderBy('sort_order')->firstOrFail();

        $other = $this->createFrame($brand, $age, 'Other', 10);
        $hit = $this->createFrame($brand, $age, 'Hit', 20, isHit: true);
        $school = $this->createFrame($brand, $age, 'School', 30, isSchoolChoice: true);
        $schoolHit = $this->createFrame($brand, $age, 'School hit', 40, isHit: true, isSchoolChoice: true);
        $this->createFrame($brand, $age, 'Hidden', 1, isSchoolChoice: true, isActive: false);
        $this->createFrame($brand, $age, 'Other 2', 50);
        $this->createFrame($brand, $age, 'Other 3', 60);

        $frames = app(FrameCatalogService::class)->schoolSlider();

        $this->assertSame(
            [$school->getKey(), $schoolHit->getKey(), $hit->getKey(), $other->getKey()],
            array_slice(array_column($frames, 'id'), 0, 4),
        );
        $this->assertCount(6, $frames);
    }

    public function test_school_slider_block_is_registered_and_uses_selected_frames(): void
    {
        $type = \App\Enums\BlockType::tryFrom(83);

        $this->assertNotNull($type);
        $this->assertSame('Отличный выбор для школы «Детская оптика»', $type->getLabel());

        $definition = app(BlockRegistry::class)->find($type);
        $this->assertNotNull($definition);
        $this->assertSame('components.block.kids-optics-school-slider', $definition->view());

        $viewData = $definition->viewData(new Block(['type' => $type, 'title' => '']));
        $this->assertSame('Отличный выбор для школы', $viewData['sliderTitle']);
    }

    public function test_school_slider_cards_stretch_to_the_tallest_slide_and_keep_meta_at_the_bottom(): void
    {
        $html = view('components.block.partials.kids-optics-frame-card', [
            'variant' => 'school-slider',
            'frame' => [
                'id' => 1,
                'ageGroups' => [],
                'genders' => ['boy'],
                'gender' => 'boy',
                'genderLabel' => 'Для мальчика',
                'title' => 'Очень длинное название модели оправы',
                'description' => 'Лёгкая, гибкая, прочная',
                'colors' => ['#1F3462'],
                'image' => ['avif' => null, 'webp' => null, 'webpSrcset' => null, 'src' => '/image.jpg', 'srcset' => null],
            ],
        ])->render();

        $this->assertStringContainsString('min-h-96 w-80 md:w-[300px]', $html);
        $this->assertStringContainsString('flex flex-1 flex-col', $html);
        $this->assertStringContainsString('mt-auto flex items-center', $html);
    }

    private function createFrame(
        FrameBrand $brand,
        FrameAgeGroup $age,
        string $model,
        int $sortOrder,
        bool $isHit = false,
        bool $isSchoolChoice = false,
        bool $isActive = true,
    ): Frame {
        $frame = Frame::query()->create([
            'brand_id' => $brand->getKey(),
            'model' => $model,
            'genders' => [FrameGender::BOY->value],
            'sort_order' => $sortOrder,
            'is_hit' => $isHit,
            'is_school_choice' => $isSchoolChoice,
            'is_active' => $isActive,
        ]);
        $frame->ageGroups()->attach($age);

        return $frame;
    }
}
