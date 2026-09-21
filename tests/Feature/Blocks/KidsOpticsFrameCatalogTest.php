<?php

namespace Tests\Feature\Blocks;

use App\Blocks\Definitions\KidsOpticsFrameCatalogDefinition;
use App\Enums\BlockType;
use App\Models\Block;
use App\Models\CuratorMedia;
use App\Models\Frame;
use App\Models\FrameAgeGroup;
use App\Models\FrameBrand;
use Filament\Forms\Components\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KidsOpticsFrameCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_server_renders_first_six_sorted_catalog_frames(): void
    {
        $brand = FrameBrand::query()->create(['name' => 'Junior']);
        $age = FrameAgeGroup::query()->orderBy('sort_order')->firstOrFail();

        foreach (range(1, 8) as $number) {
            $frame = Frame::query()->create([
                'brand_id' => $brand->id,
                'model' => "Model {$number}",
                'genders' => ['boy'],
                'sort_order' => $number * 10,
            ]);
            $frame->ageGroups()->attach($age);
        }

        $definition = app(KidsOpticsFrameCatalogDefinition::class);
        $block = new Block([
            'type' => BlockType::KIDS_OPTICS_FRAME_CATALOG,
            'title' => 'Каталог оправ',
            'payload' => [
                'items' => [['title' => 'Legacy card must not render']],
                'show_more_desktop_text' => 'Ещё оправы',
                'show_more_mobile_text' => 'Ещё',
            ],
        ]);
        $viewData = $definition->viewData($block);
        $html = view($definition->view(), $viewData)->render();

        $this->assertCount(6, $viewData['frames']);
        $this->assertSame(8, $viewData['totalFrames']);
        $this->assertSame('Junior Model 1', $viewData['frames'][0]['title']);
        $this->assertSame(6, substr_count($html, 'data-frame-catalog-card'));
        $this->assertStringNotContainsString('Legacy card must not render', $html);
        $this->assertStringContainsString('/api/frame-catalog', $html);
    }

    public function test_block_form_contains_only_block_settings_and_no_frame_repeater(): void
    {
        $definition = app(KidsOpticsFrameCatalogDefinition::class);

        $this->assertSame(
            ['payload.show_more_desktop_text', 'payload.show_more_mobile_text'],
            $this->componentNames($definition->formSchema()),
        );
    }

    public function test_curator_media_is_protected_while_used_by_a_frame(): void
    {
        $media = CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'kids-optics/frames',
            'visibility' => 'public',
            'name' => 'frame',
            'path' => 'kids-optics/frames/frame.jpg',
            'type' => 'image/jpeg',
            'ext' => 'jpg',
        ]);
        $brand = FrameBrand::query()->create(['name' => 'Safe']);
        Frame::query()->create([
            'brand_id' => $brand->id,
            'curator_media_id' => $media->id,
            'model' => 'Protected',
            'genders' => ['girl'],
        ]);

        $this->assertTrue($media->isUsedByBlocks());
    }

    /** @param array<Component> $components */
    private function componentNames(array $components): array
    {
        $names = [];

        foreach ($components as $component) {
            if (method_exists($component, 'getName')) {
                $names[] = $component->getName();
            }

            if (method_exists($component, 'getChildComponents')) {
                $names = [...$names, ...$this->componentNames($component->getChildComponents())];
            }
        }

        return $names;
    }
}
