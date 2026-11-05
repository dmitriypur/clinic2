<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeFileUpload;
use App\Filament\Resources\BlockResource\Pages\CreateBlock;
use App\Filament\Resources\BlockResource\Pages\EditBlock;
use App\Models\Block;
use App\Models\Page;
use App\Models\Staff;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
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
        Storage::fake('public');

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
        foreach (['create_block', 'view_any_block', 'view_block', 'update_block'] as $permission) {
            Permission::query()->create([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }
        $staff->givePermissionTo('create_block', 'view_any_block', 'view_block', 'update_block');
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

        $this->saveExistingBlock($block);

        $this->assertSame('<p>Legacy content</p>', $block->fresh()->body_html);
    }

    public function test_diagnostic_assessment_can_be_configured_and_rendered_as_a_page_block(): void
    {
        $this->createBlock([
            'title' => 'Что мы оцениваем на диагностике',
            'type' => BlockType::DIAGNOSTIC_ASSESSMENT->value,
            'payload' => [
                'assessment_items' => ['first' => ['text' => 'Внимание'], 'second' => ['text' => 'Понимание текста']],
                'assessment_description' => 'Результаты <тестов> и план работы.',
            ],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(['Внимание', 'Понимание текста'], $block->payload['assessment_items']);

        $html = Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $block],
        );

        $this->assertStringContainsString('Что мы оцениваем на диагностике', $html);
        $this->assertStringContainsString('Понимание текста', $html);
        $this->assertStringContainsString('Результаты &lt;тестов&gt; и план работы.', $html);
        $this->assertStringContainsString('diagnostic-assessment/desktop.webp', $html);
        $this->assertStringContainsString('diagnostic-assessment/mobile.webp', $html);
        $this->assertStringContainsString('openBookingWidgetV3', $html);
    }

    public function test_registered_diagnostic_methods_schema_preserves_payload_and_existing_media(): void
    {
        $mediaCollection = 'diagnostic-card';

        $this->createBlock([
            'title' => 'Методы диагностики',
            'type' => BlockType::DIAGNOSTIC_METHODS->value,
            'body_html' => '<p>Вводный текст</p>',
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

        $media = $block
            ->addMedia(UploadedFile::fake()->image('existing-diagnostic.jpg'))
            ->toMediaCollection('default');
        $payloadBefore = $block->payload;

        $this->saveExistingBlock($block);

        $block->refresh();
        $this->assertSame($payloadBefore, $block->payload);
        $this->assertSame($media->getKey(), $block->getFirstMedia('default')?->getKey());
    }

    public function test_registered_treatment_methods_schema_saves_payload_and_nested_media(): void
    {
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
                        'link' => '/legacy-treatment-link',
                        'legacy_note' => 'Сохранить при редактировании',
                    ],
                ],
            ],
        ], [
            'data.payload.items.treatment-one.image' => [UploadedFile::fake()->image('treatment.jpg')],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(BlockType::TREATMENT_METHODS, $block->type);
        $this->assertSame($mediaCollection, $block->payload['items'][0]['media_collection']);
        $this->assertSame('/legacy-treatment-link', $block->payload['items'][0]['link']);
        $this->assertSame('Сохранить при редактировании', $block->payload['items'][0]['legacy_note']);
        $this->assertSame('Перед методами', $block->payload['cards_intro']);
        $block->clearMediaCollection($mediaCollection);
        $media = $block
            ->addMedia(UploadedFile::fake()->image('existing-treatment.jpg'))
            ->toMediaCollection($mediaCollection);

        $payloadBefore = $block->payload;

        $this->saveExistingBlock($block);

        $block->refresh();
        $this->assertSame($payloadBefore, $block->payload);
        $this->assertSame($media->getKey(), $block->getFirstMedia($mediaCollection)?->getKey());
    }

    public function test_spatie_media_image_field_rejects_svg_uploads(): void
    {
        Livewire::test(CreateBlock::class)
            ->fillForm([
                'page_id' => $this->page->id,
                'anchor' => 'unsafe-media-block',
                'title' => 'Unsafe media block',
                'settings' => [
                    'title_hidden' => false,
                    'show_page_title' => false,
                    'breadcrumbs' => false,
                    'show_on_mobile' => true,
                    'hide_on_desctop' => false,
                ],
            ])
            ->set('data.type', BlockType::TEXT_WITH_IMAGE->value)
            ->set('data.default', [UploadedFile::fake()->createWithContent(
                'active.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            )])
            ->call('create')
            ->assertHasFormErrors(['default']);

        $this->assertDatabaseMissing('blocks', ['anchor' => 'unsafe-media-block']);
    }

    public function test_grid_contacts_uses_the_safe_public_image_profile(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::GRID_CONTACTS);
        $this->assertNotNull($definition);

        $upload = $this->findComponent($definition->formSchema(), 'payload.image');

        $this->assertInstanceOf(SafeFileUpload::class, $upload);
        $this->assertSame(
            ['image/jpeg', 'image/png', 'image/webp'],
            $upload->getAcceptedFileTypes(),
        );
    }

    public function test_text_with_image_saves_service_layout_and_image_side(): void
    {
        $this->createBlock([
            'title' => 'Услуга',
            'type' => BlockType::TEXT_WITH_IMAGE_NEW->value,
            'body_html' => '<p>Текст</p>',
            'payload' => ['layout_variant' => 'service', 'image_position' => 'left'],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();
        $this->assertSame('service', $block->payload['layout_variant']);
        $this->assertSame('left', $block->payload['image_position']);
        $this->assertSame('<p>Текст</p>', $block->body_html);
        $media = $block->addMedia(UploadedFile::fake()->image('example.jpg'))->toMediaCollection('default');
        $this->saveExistingBlock($block);
        $this->assertSame('left', $block->fresh()->payload['image_position']);
        $this->assertSame($media->id, $block->fresh()->getFirstMedia('default')?->id);
    }

    public function test_text_with_image_upload_creates_responsive_webp(): void
    {
        $block = Block::query()->create([
            'page_id' => $this->page->id,
            'title' => 'Изображение',
            'type' => BlockType::TEXT_WITH_IMAGE_NEW,
            'payload' => ['layout_variant' => 'service'],
        ]);

        $media = $block->addMedia(UploadedFile::fake()->image('photo.png', 1600, 1200))
            ->toMediaCollection('default');
        $media->refresh();

        $this->assertTrue($media->hasGeneratedConversion('text-image'));
        $this->assertTrue($media->hasResponsiveImages('text-image'));
        $this->assertSame('image/webp', mime_content_type($media->getPath('text-image')));
        $this->assertFileExists($media->getPath());
        $this->assertStringContainsString('.webp', $media->getSrcset('text-image'));
        $rendered = $block->fresh()->getResponsiveImage('default', 'Изображение', 'text-image')->lazy()->toHtml();
        $this->assertStringContainsString('srcset=', $rendered);
        $this->assertStringContainsString('.webp', $rendered);
        $this->assertStringContainsString('loading="lazy"', $rendered);

        $media->unsetRelation('model');
        $media->preventsLazyLoading = true;
        $this->assertSame('text-image', \Spatie\MediaLibrary\Conversions\ConversionCollection::createForMedia($media)->getByName('text-image')->getName());
    }

    public function test_service_hero_saves_button_link_and_converts_uploaded_image(): void
    {
        $this->createBlock([
            'title' => 'Услуга',
            'type' => BlockType::SERVICE_HERO->value,
            'payload' => ['button_text' => 'Подробнее', 'button_url' => '/services'],
        ], ['data.payload.image' => [UploadedFile::fake()->image('hero.jpg', 80, 40)]]);

        $block = Block::query()->latest('id')->firstOrFail();
        $this->assertSame('/services', $block->payload['button_url']);
        $image = $block->payload['image'];
        Storage::disk('public')->assertExists([$image, app(\App\Services\ServiceHeroImageService::class)->webpPath($image)]);
    }

    public function test_horizontal_cards_render_responsive_webp_after_image_conversion(): void
    {
        config(['media-library.queue_conversions_by_default' => true]);

        $this->createBlock([
            'title' => 'Ключевые задачи занятий',
            'type' => BlockType::CARDS_ITEM_ROW->value,
            'payload' => [
                'columns' => 3,
                'elements' => ['first' => [
                    'title' => 'Тренировать память и внимание',
                    'media_collection' => 'horizontal-card',
                    'body_html' => null,
                ]],
            ],
        ], ['data.payload.elements.first.image' => [UploadedFile::fake()->image('card.png', 800, 800)]]);

        $block = Block::query()->latest('id')->firstOrFail();
        $media = $block->getFirstMedia('horizontal-card');
        $this->assertNotNull($media);

        $media->refresh();

        $this->assertFalse($media->hasGeneratedConversion('main'));
        $definition = app(BlockRegistry::class)->find(BlockType::CARDS_ITEM_ROW);
        $htmlBefore = view($definition->view(), ['block' => $block, ...$definition->viewData($block)])->render();
        $this->assertStringContainsString('src="'.$media->getUrl().'"', $htmlBefore);
        $this->assertStringNotContainsString($media->getUrl('main'), $htmlBefore);
        $sharedPage = Page::query()->create(['title' => 'Shared page', 'handle' => 'shared-page', 'active' => true]);
        $block->pages()->attach($sharedPage);
        Cache::put('page-global-block-test-page', $this->page->load('blocks.media'), 3600);
        Cache::put('page-global-shared-page', 'cached', 3600);
        Cache::put('page-global-unrelated', 'cached', 3600);
        Queue::assertPushed(PerformConversionsJob::class);
        Queue::pushed(PerformConversionsJob::class)->each(
            fn (PerformConversionsJob $job) => $job->handle(app(FileManipulator::class))
        );
        $media->refresh();

        $this->assertNull(Cache::get('page-global-block-test-page'));
        $this->assertNull(Cache::get('page-global-shared-page'));
        $this->assertSame('cached', Cache::get('page-global-unrelated'));
        $this->assertTrue($media->hasGeneratedConversion('main'));
        $this->assertTrue($media->hasResponsiveImages('main'));
        $this->assertSame('image/webp', mime_content_type($media->getPath('main')));
        $this->assertFileExists($media->getPath());

        foreach ([3 => '64px', 2 => '72px'] as $columns => $sizes) {
            $block->refresh();
            $block->payload = [...$block->payload, 'columns' => $columns];
            $html = view($definition->view(), ['block' => $block, ...$definition->viewData($block)])->render();

            $this->assertStringContainsString('src="'.$media->getUrl('main').'"', $html);
            $this->assertStringContainsString('srcset=', $html);
            $this->assertStringContainsString('400w', $html);
            $this->assertStringContainsString('sizes="'.$sizes.'"', $html);
            $this->assertStringContainsString('loading="lazy"', $html);
            $this->assertStringContainsString('decoding="async"', $html);
            $this->assertStringNotContainsString('data:image/', $html);
            $this->assertStringNotContainsString('onload=', $html);
            $this->assertStringNotContainsString($media->getUrl().'"', $html);
        }
    }

    public function test_horizontal_cards_registry_preserves_existing_payload_media_and_markup(): void
    {
        $definition = app(BlockRegistry::class)->find(BlockType::CARDS_ITEM_ROW);
        $this->assertNotNull($definition);
        $this->assertSame('components.block.cards-row', $definition->view());

        $block = Block::query()->create([
            'page_id' => $this->page->id,
            'title' => 'Ключевые задачи занятий',
            'type' => BlockType::CARDS_ITEM_ROW,
            'settings' => [
                'title_hidden' => false,
                'show_page_title' => false,
                'breadcrumbs' => false,
                'show_on_mobile' => true,
                'hide_on_desctop' => false,
                'background' => '1',
            ],
            'payload' => [
                'columns' => 3,
                'elements' => [[
                    'title' => 'Тренировать память и внимание',
                    'subtitle' => 'Подзаголовок',
                    'body_html' => '<p>Подробное описание</p>',
                    'media_collection' => 'horizontal-card',
                    'legacy_note' => 'Сохранить при редактировании',
                ]],
            ],
        ]);
        $media = $block->addMedia(UploadedFile::fake()->image('existing-card.jpg'))
            ->toMediaCollection('horizontal-card');
        $media->markAsConversionNotGenerated('main');
        $payloadBefore = $block->payload;
        $render = fn (Block $record): string => Blade::render(
            '<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />',
            ['block' => $record],
        );
        $htmlBefore = $render($block);
        $this->assertStringContainsString(
            trim(view($definition->view(), ['block' => $block, ...$definition->viewData($block)])->render()),
            $htmlBefore,
        );
        $this->assertSame(1, substr_count($htmlBefore, '<h2'));
        $this->assertStringContainsString('Тренировать память и внимание', $htmlBefore);
        $this->assertStringContainsString($media->getUrl(), $htmlBefore);
        $this->assertStringNotContainsString($media->getUrl('main'), $htmlBefore);

        $this->saveExistingBlock($block);
        $block->refresh();

        $this->assertSame($payloadBefore, $block->payload);
        $this->assertSame($media->id, $block->getFirstMedia('horizontal-card')?->id);
        $this->assertSame($htmlBefore, $render($block));
    }

    public function test_horizontal_cards_default_to_two_columns_when_editing_a_legacy_block(): void
    {
        $block = Block::query()->create([
            'page_id' => $this->page->id,
            'title' => 'Горизонтальные карточки',
            'type' => BlockType::CARDS_ITEM_ROW,
            'payload' => ['elements' => []],
        ]);

        Livewire::test(EditBlock::class, ['record' => $block->getRouteKey()])
            ->assertFormFieldExists('payload.columns', fn ($field): bool => $field instanceof Select)
            ->assertFormFieldIsVisible('payload.columns')
            ->assertFormSet(['payload.columns' => 2]);
    }

    public function test_horizontal_cards_preserve_three_columns_when_saved_again(): void
    {
        $this->createBlock([
            'title' => 'Ключевые задачи занятий',
            'type' => BlockType::CARDS_ITEM_ROW->value,
            'payload' => ['columns' => 3, 'elements' => []],
        ]);

        $block = Block::query()->latest('id')->firstOrFail();

        $this->assertSame(3, (int) $block->payload['columns']);
        $this->saveExistingBlock($block);
        $this->assertSame(3, (int) $block->fresh()->payload['columns']);
    }

    public function test_horizontal_cards_reject_unsupported_column_counts(): void
    {
        $block = Block::query()->create([
            'page_id' => $this->page->id,
            'title' => 'Горизонтальные карточки',
            'type' => BlockType::CARDS_ITEM_ROW,
            'payload' => ['columns' => 2, 'elements' => []],
        ]);

        Livewire::test(EditBlock::class, ['record' => $block->getRouteKey()])
            ->fillForm(['payload.columns' => 4])
            ->call('save')
            ->assertHasFormErrors(['payload.columns']);

        $this->assertSame(2, (int) $block->fresh()->payload['columns']);
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

    private function saveExistingBlock(Block $block): void
    {
        Livewire::test(EditBlock::class, ['record' => $block->getRouteKey()])
            ->assertStatus(200)
            ->call('save')
            ->assertHasNoFormErrors();
    }

    /**
     * @param  array<Component>  $components
     */
    private function findComponent(array $components, string $name): ?Component
    {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $component->getName() === $name) {
                return $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $match = $this->findComponent($component->getChildComponents(), $name);

                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }
}
