<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\CuratorMedia;
use App\Models\Doctor;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpertOpinionCuratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_curator_uses_its_own_table_without_replacing_spatie_media(): void
    {
        $this->assertTrue(Schema::hasTable('media'));
        $this->assertTrue(Schema::hasTable('curator_media'));
    }

    public function test_expert_opinion_uses_selected_curator_image(): void
    {
        $media = CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'expert',
            'path' => 'media/expert.png',
            'type' => 'image/png',
            'ext' => 'png',
        ]);
        Storage::disk('public')->put($media->path, 'image');
        $block = new Block([
            'type' => BlockType::EXPERT_OPINION,
            'payload' => ['curator_image_id' => $media->getKey()],
        ]);

        $this->assertTrue($block->has_image);
        $this->assertTrue($media->is($block->expert_opinion_image));
    }

    public function test_expert_opinion_does_not_use_spatie_default_media(): void
    {
        Storage::fake('public');
        $page = Page::query()->create([
            'title' => 'Статья',
            'handle' => 'expert-opinion-test',
            'active' => true,
        ]);
        $block = Block::query()->create([
            'page_id' => $page->getKey(),
            'type' => BlockType::EXPERT_OPINION,
            'title' => 'Мнение эксперта',
            'payload' => [],
        ]);
        $legacyMedia = $block->media()->create([
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'default',
            'name' => 'legacy-expert',
            'file_name' => 'legacy-expert.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 12,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);
        Storage::disk('public')->put($legacyMedia->getPathRelativeToRoot(), 'legacy-image');

        $this->assertFalse($block->fresh()->has_image);
        $this->assertNull($block->fresh()->expert_opinion_image);
    }

    public function test_expert_opinion_view_renders_selected_curator_image(): void
    {
        Storage::fake('public');
        $doctor = Doctor::query()->create([
            'name' => 'Иван',
            'surname' => 'Иванов',
            'speciality' => 'Офтальмолог',
            'job_title' => 'Врач',
            'bio' => 'Биография',
        ]);
        $page = Page::query()->create([
            'title' => 'Статья',
            'handle' => 'expert-opinion-curator-view',
            'active' => true,
        ]);
        $media = CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'expert-opinions',
            'visibility' => 'public',
            'name' => 'expert',
            'path' => 'expert-opinions/expert.png',
            'type' => 'image/png',
            'ext' => 'png',
        ]);
        Storage::disk('public')->put($media->path, 'image');
        $block = Block::query()->create([
            'page_id' => $page->getKey(),
            'type' => BlockType::EXPERT_OPINION,
            'title' => 'Мнение эксперта',
            'body_html' => '<p>Комментарий врача.</p>',
            'payload' => [
                'author' => $doctor->getKey(),
                'curator_image_id' => $media->getKey(),
            ],
        ]);

        $html = view('components.block.expert-opinion', ['block' => $block])->render();

        $this->assertStringContainsString('srcset="', $html);
        $this->assertStringContainsString(' 480w', $html);
        $this->assertStringContainsString(' 768w', $html);
        $this->assertStringContainsString(' 1024w', $html);
        $this->assertStringContainsString('fm=webp', $html);
        $this->assertStringContainsString('q=82', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('responsive-image', $html);
    }
}
