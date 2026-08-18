<?php

namespace Tests\Feature\Blocks;

use App\Enums\BlockType;
use App\Filament\Resources\BlockResource\Pages\CreateBlock;
use App\Models\Block;
use App\Models\Page;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlockResourceRegistryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        if (! Schema::hasColumn('blocks', 'settings')) {
            Schema::table('blocks', function (Blueprint $table): void {
                $table->json('settings')->nullable();
            });
        }

        $staff = Staff::query()->create([
            'name' => 'Block Admin',
            'email' => 'blocks@example.test',
            'password' => 'password',
        ]);
        foreach (['create_block', 'view_any_block'] as $permission) {
            Permission::query()->create([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }
        $staff->givePermissionTo('create_block', 'view_any_block');
        $this->actingAs($staff, 'staff');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->page = Page::query()->create([
            'title' => 'Block test page',
            'handle' => 'block-test-page',
            'active' => true,
        ]);
    }

    public function test_registered_reception_steps_schema_is_hydrated_and_saved_by_block_resource(): void
    {
        $items = [
            'step-one' => ['title' => 'Первый этап', 'body_html' => '<p>Описание</p>'],
        ];

        $this->createBlock([
            'title' => 'Этапы приёма',
            'type' => BlockType::RECEPTION_STEPS->value,
            'payload' => ['items' => $items],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(BlockType::RECEPTION_STEPS, $block->type);
        $this->assertSame(array_values($items), $block->payload['items']);
        $this->assertFalse($block->settings['breadcrumbs']);
        $this->assertTrue($block->settings['show_on_mobile']);
    }

    public function test_legacy_html_schema_still_saves_through_block_resource(): void
    {
        $this->createBlock([
            'title' => 'Текстовый блок',
            'type' => BlockType::HTML->value,
            'body_html' => '<p>Legacy content</p>',
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(BlockType::HTML, $block->type);
        $this->assertSame('<p>Legacy content</p>', $block->body_html);
        $this->assertTrue($block->settings['show_on_mobile']);
    }

    public function test_registered_diagnostic_methods_schema_saves_payload_and_media(): void
    {
        Storage::fake('public');
        $mediaCollection = 'diagnostic-card';

        $this->createBlock([
            'title' => 'Методы диагностики',
            'type' => BlockType::DIAGNOSTIC_METHODS->value,
            'body_html' => '<p>Вводный текст</p>',
            'default' => [UploadedFile::fake()->image('diagnostic.jpg')],
            'payload' => [
                'cards_intro' => 'Перед карточками',
                'items' => [
                    'diagnostic-one' => [
                        'title' => 'Диагностика',
                        'body_html' => '<p>Описание</p>',
                        'link' => '/diagnostic',
                        'media_collection' => $mediaCollection,
                    ],
                ],
            ],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(BlockType::DIAGNOSTIC_METHODS, $block->type);
        $this->assertSame($mediaCollection, $block->payload['items'][0]['media_collection']);
        $this->assertSame('Перед карточками', $block->payload['cards_intro']);
        $this->assertCount(1, $block->getMedia('default'));
    }

    public function test_registered_treatment_methods_schema_saves_payload_and_nested_media(): void
    {
        Storage::fake('public');
        $mediaCollection = 'treatment-card';

        $this->createBlock([
            'title' => 'Методы лечения',
            'type' => BlockType::TREATMENT_METHODS->value,
            'body_html' => '<p>Вводный текст</p>',
            'payload' => [
                'cards_intro' => 'Перед методами',
                'items' => [
                    'treatment-one' => [
                        'title' => 'Лечение',
                        'body_html' => '<p>Описание</p>',
                        'media_collection' => $mediaCollection,
                    ],
                ],
            ],
        ], [
            'data.payload.items.treatment-one.image' => [UploadedFile::fake()->image('treatment.jpg')],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(BlockType::TREATMENT_METHODS, $block->type);
        $this->assertSame($mediaCollection, $block->payload['items'][0]['media_collection']);
        $this->assertSame('Перед методами', $block->payload['cards_intro']);
        $this->assertCount(1, $block->getMedia($mediaCollection));
    }

    private function createBlock(array $state, array $uploads = []): void
    {
        $type = $state['type'];

        $component = Livewire::test(CreateBlock::class)
            ->assertStatus(200)
            ->fillForm([
                'page_id' => $this->page->id,
                'anchor' => 'test-block',
                'title' => $state['title'],
                'settings' => [
                    'title_hidden' => false,
                    'show_page_title' => false,
                    'breadcrumbs' => false,
                    'show_on_mobile' => true,
                    'hide_on_desctop' => false,
                ],
            ])
            ->set('data.type', $type)
            ->fillForm(array_diff_key($state, ['payload' => true]));

        if (isset($state['payload'])) {
            $component->set('data.payload', $state['payload']);
        }

        foreach ($uploads as $path => $files) {
            $component->set($path, $files);
        }

        $component->call('create')
            ->assertHasNoFormErrors();
    }
}
