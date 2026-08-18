<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\CuratorMedia;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportExpertOpinionMediaToCuratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_change_expert_block_or_files(): void
    {
        Storage::fake('public');
        $block = $this->createLegacyExpertBlock();

        $this->artisan('expert-opinion:import-to-curator', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertNull(data_get($block->fresh()->payload, 'curator_image_id'));
        $this->assertDatabaseCount('curator_media', 0);
        $this->assertNotNull($block->fresh()->getFirstMedia('default'));
    }

    public function test_command_copies_legacy_image_and_is_idempotent(): void
    {
        Storage::fake('public');
        $block = $this->createLegacyExpertBlock();
        $legacyMedia = $block->getFirstMedia('default');

        $this->artisan('expert-opinion:import-to-curator')->assertSuccessful();

        $block = $block->fresh();
        $curatorMedia = CuratorMedia::query()->findOrFail(
            data_get($block->payload, 'curator_image_id')
        );

        Storage::disk($curatorMedia->disk)->assertExists($curatorMedia->path);
        $this->assertSame('public', Storage::disk($curatorMedia->disk)->getVisibility($curatorMedia->path));
        Storage::disk($legacyMedia->disk)->assertExists($legacyMedia->getPathRelativeToRoot());
        $this->assertNotNull($block->getFirstMedia('default'));

        $this->artisan('expert-opinion:import-to-curator')->assertSuccessful();

        $this->assertDatabaseCount('curator_media', 1);
        $this->assertSame($curatorMedia->id, data_get($block->fresh()->payload, 'curator_image_id'));
    }

    private function createLegacyExpertBlock(): Block
    {
        $page = Page::query()->create([
            'title' => 'Статья',
            'handle' => 'legacy-expert-'.uniqid(),
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

        return $block->fresh();
    }
}
