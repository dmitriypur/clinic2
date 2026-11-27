<?php

namespace Tests\Feature\Blocks;

use App\Blocks\BlockRegistry;
use App\Enums\BlockType;
use App\Filament\Forms\Components\SafeFileUpload;
use App\Filament\Resources\BlockResource\Pages\CreateBlock;
use App\Filament\Resources\BlockResource\Pages\EditBlock;
use App\Filament\Resources\BlockResource\Pages\ListBlocks;
use App\Models\Block;
use App\Models\City;
use App\Models\Page;
use App\Models\Staff;
use App\Services\CityService;
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
use Illuminate\Support\Facades\View;
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

    public function test_block_list_shows_types_unused_by_any_page_in_a_collapsed_list(): void
    {
        $inactivePage = Page::query()->create([
            'title' => 'Inactive block test page',
            'handle' => 'inactive-block-test-page',
            'active' => false,
        ]);

        foreach ([$this->page, $inactivePage] as $page) {
            $page->blocks()->create(['type' => BlockType::HTML, 'title' => 'Текст']);
        }
        $inactivePage->blocks()->create(['type' => BlockType::TEXT_SUBDUED, 'title' => 'Второстепенный текст']);

        $unusedCount = count(BlockType::cases()) - 2;

        Livewire::test(ListBlocks::class)
            ->assertSee('Неиспользуемых типов блоков — '.$unusedCount)
            ->assertSeeHtml('<details>')
            ->assertSeeHtml('<li>Филиалы</li>')
            ->assertDontSeeHtml('<li>Второстепенный текст</li>');
    }

    public function test_migrated_blocks_preserve_existing_data_and_render(): void
    {
        $cases = [
            [BlockType::APPARATUS_TASKS, null, ['tasks' => [['text' => 'Задача терапии']], 'note_text' => 'Примечание'], 'Задача терапии'],
            [BlockType::TEXT_SUBDUED, '<p>Второстепенный контент</p>', [], 'Второстепенный контент'],
            [BlockType::HTML_CODE, '<div>HTML контент</div>', [], 'HTML контент'],
            [BlockType::HOW_TO_ORDER, null, ['order' => [['title' => 'Первый шаг', 'text' => '<p>Описание шага</p>']]], 'Описание шага'],
            [BlockType::TAGS, null, ['tags' => [['title' => 'Раздел статьи', 'link' => '#section']]], '#section"'],
            [BlockType::FAQ, null, ['faq' => [['question' => 'Вопрос статьи', 'answer_html' => '<p>Ответ статьи</p>', 'icon' => null]]], '<p>Ответ статьи</p>'],
        ];

        foreach ($cases as [$type, $body, $payload, $expected]) {
            $this->assertTrue(app(BlockRegistry::class)->has($type));
            $block = Block::query()->create([
                'page_id' => $this->page->id, 'title' => 'Проверка', 'type' => $type,
                'body_html' => $body, 'payload' => $payload,
            ]);
            $this->saveExistingBlock($block);
            $block->refresh();
            $this->assertSame($body, $block->body_html);
            $this->assertSame($payload, $block->payload);
            $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
            $this->assertStringContainsString($expected, $html);

            $this->createBlock([
                'title' => 'Новый блок', 'type' => $type->value,
                'body_html' => $body, 'payload' => $payload,
            ]);
            $created = Block::query()->latest('id')->firstOrFail();
            $this->assertSame($body, $created->body_html);
            $this->assertSame($payload, $created->payload ?? []);
        }
    }

    public function test_apparatus_chart_and_guarantee_blocks_preserve_existing_content_and_media(): void
    {
        $cases = [
            [
                BlockType::APPARATUS_DISEASES,
                '<p>Вводный текст о заболеваниях</p>',
                ['items' => [['text' => 'Карточка заболевания']]],
                ['Вводный текст о заболеваниях', 'Карточка заболевания'],
                ['default', 'default'],
            ],
            [
                BlockType::TEXT_WITH_CHART,
                null,
                [
                    'var_1_title' => 'Первый вариант',
                    'var_1_text' => 'Описание первого варианта',
                    'var_2_title' => 'Второй вариант',
                    'var_2_text' => 'Описание второго варианта',
                ],
                ['Первый вариант', 'Описание первого варианта', 'Второй вариант', 'Описание второго варианта'],
                ['bg_chart', 'payload.bg_chart'],
            ],
            [
                BlockType::GUARANTEE,
                null,
                [
                    'enter_text' => '<p>Условия программы</p>',
                    'guarantee' => [['title' => 'Название гарантии', 'text' => 'Описание гарантии']],
                ],
                ['Условия программы', 'Название гарантии', 'Описание гарантии', 'images/guarantee/boy.jpg'],
                null,
            ],
        ];

        foreach ($cases as [$type, $body, $payload, $expected, $upload]) {
            $this->assertTrue(app(BlockRegistry::class)->has($type), $type->name);

            $block = $this->page->blocks()->create([
                'title' => 'Существующий блок',
                'type' => $type,
                'body_html' => $body,
                'payload' => $payload,
                'settings' => ['title_hidden' => true, 'show_on_mobile' => true],
            ]);
            $media = $upload
                ? $block->addMedia(UploadedFile::fake()->image('existing.jpg'))->toMediaCollection($upload[0])
                : null;
            $this->saveExistingBlock($block);
            $block->refresh();

            $this->assertSame($body, $block->body_html);
            $this->assertSame($payload, $block->payload);
            $this->assertTrue($block->title_hidden);
            $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
            foreach ($expected as $content) {
                $this->assertStringContainsString($content, $html);
            }
            if ($media !== null) {
                $this->assertSame($media->id, $block->getFirstMedia($upload[0])?->id);
                $this->assertStringContainsString($media->getUrl(), $html);
            }

            $this->createBlock([
                'title' => 'Новый блок',
                'type' => $type->value,
                'body_html' => $body,
                'payload' => $payload,
            ], $upload ? ['data.'.$upload[1] => [UploadedFile::fake()->image('new.jpg')]] : []);
            $created = $this->page->blocks()->reorder('id', 'desc')->firstOrFail();
            $this->assertSame($type, $created->type);
            $this->assertSame($body, $created->body_html);
            $this->assertEquals($payload, $created->payload);
            if ($upload !== null) {
                $this->assertNotNull($created->getFirstMedia($upload[0]));
            }
        }
    }

    public function test_details_cards_render_the_current_city_details(): void
    {
        $this->assertTrue(app(BlockRegistry::class)->has(BlockType::DETAILS_ALT));
        $block = $this->page->blocks()->create([
            'type' => BlockType::DETAILS_ALT,
            'title' => 'Наши реквизиты',
        ]);
        $this->saveExistingBlock($block);

        $detailsByCity = [
            'Киров' => [['name' => 'Клиника Кирова', 'legal_address' => 'Кировский адрес']],
            'Москва' => [['name' => 'Клиника Москвы', 'legal_address' => 'Московский адрес']],
        ];

        foreach ($detailsByCity as $city => $details) {
            View::share('currentCity', new City(['name' => $city, 'details' => $details]));
            $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block->refresh()]);
            $this->assertStringContainsString('Наши реквизиты', $html);
            $this->assertStringContainsString($details[0]['name'], $html);
            $this->assertStringContainsString($details[0]['legal_address'], $html);
        }
    }

    public function test_original_text_with_image_saves_type_zero_and_preserves_layout_and_image(): void
    {
        $type = BlockType::TEXT_WITH_IMAGE;
        $this->assertTrue(app(BlockRegistry::class)->has($type));

        $payload = ['link' => $this->page->handle, 'reverse' => true, 'add_fox' => false];
        $body = '<p>Сохранённый текст с изображением</p>';
        $this->createBlock([
            'title' => 'Текст с изображением',
            'type' => $type->value,
            'body_html' => $body,
            'payload' => $payload,
        ], ['data.default' => [UploadedFile::fake()->image('existing.jpg')]]);

        $block = $this->page->blocks()->where('type', $type->value)->sole();
        $media = $block->getFirstMedia('default');
        $this->assertNotNull($media);
        $this->saveExistingBlock($block);
        $block->refresh();

        $this->assertSame($body, $block->body_html);
        $this->assertSame($payload, $block->payload);
        $this->assertSame($media->id, $block->getFirstMedia('default')?->id);

        app(CityService::class)->setCurrentCity(new City(['slug' => 'moskva']));
        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
        $this->assertMatchesRegularExpression('/<div class="container">\s*<div\s+class="flex flex-col-reverse lg:flex-row-reverse/', $html);
        $this->assertStringContainsString('/moskva/'.$this->page->handle.'"', $html);
        $this->assertStringContainsString($media->getUrl(), $html);
        $this->assertStringContainsString($body, $html);
        $this->assertStringNotContainsString('images/fireworks.webp', $html);
    }

    public function test_text_blocks_preserve_saved_grid_picture_and_opening_settings(): void
    {
        $type = BlockType::TEXT_BLOCKS;
        $this->assertTrue(app(BlockRegistry::class)->has($type));
        $picture = 'text-block-picture.jpg';
        Storage::disk('public')->put($picture, UploadedFile::fake()->image($picture)->get());
        $payload = [
            'is_opening' => true,
            'is_rounded' => false,
            'text_content' => [[
                'is_grid' => true,
                'title' => 'Заголовок текста',
                'subtitle' => 'Подзаголовок текста',
                'body_html' => '<p>Основной текст</p>',
                'picture' => $picture,
                'grid_blocks' => [['body_html' => '<p>Ячейка сетки</p>', 'col_count' => '2']],
                'cols_count' => '3',
                'classes' => ['text-center'],
            ]],
        ];

        $block = $this->page->blocks()->create(['type' => $type, 'title' => 'Текстовые блоки', 'payload' => $payload]);
        $this->saveExistingBlock($block);
        $block->refresh();

        $this->assertSame($payload, $block->payload);
        Storage::disk('public')->assertExists($picture);
        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
        $this->assertStringContainsString(':is-opening="1"', $html);
        $this->assertStringContainsString('md:grid-cols-3', $html);
        $this->assertStringContainsString('lg:col-span-2', $html);
        $this->assertStringContainsString('<p>Ячейка сетки</p>', $html);
        $this->assertStringContainsString('/storage/'.$picture, $html);
        $this->assertStringContainsString('Смотреть статью полностью', $html);
        $this->assertStringNotContainsString('rounded-20', $html);
    }

    public function test_legacy_advantages_block_keeps_its_chart_image_field(): void
    {
        $block = $this->page->blocks()->create([
            'title' => 'Преимущества',
            'type' => BlockType::ADVANTAGES,
            'payload' => ['advantages' => [['alt_image' => 'Иконка', 'text' => 'Преимущество']]],
        ]);
        $media = $block->addMedia(UploadedFile::fake()->image('advantages.jpg'))->toMediaCollection('bg_chart');

        Livewire::test(EditBlock::class, ['record' => $block->getRouteKey()])
            ->assertFormFieldIsVisible('payload.bg_chart')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($media->id, $block->fresh()->getFirstMedia('bg_chart')?->id);
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

    public function test_html_remains_the_default_and_saves_through_block_resource(): void
    {
        Livewire::test(CreateBlock::class)->assertFormSet(['type' => BlockType::HTML->value]);

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

    public function test_video_blocks_preserve_existing_files_and_render_their_urls(): void
    {
        foreach ([BlockType::VIDEO, BlockType::VIDEO_NEW] as $type) {
            $this->assertTrue(app(BlockRegistry::class)->has($type));
            $block = Block::query()->create([
                'page_id' => $this->page->id,
                'title' => 'Видео',
                'type' => $type,
            ]);
            $video = $block->addMedia(UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'))->toMediaCollection('video');
            $cover = $block->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

            Livewire::test(EditBlock::class, ['record' => $block->getRouteKey()])
                ->assertFormFieldIsVisible('video')
                ->assertFormFieldIsVisible('cover')
                ->call('save')
                ->assertHasNoFormErrors();
            $block->refresh();

            $this->assertSame($video->id, $block->getFirstMedia('video')?->id);
            $this->assertSame($cover->id, $block->getFirstMedia('cover')?->id);
            $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
            $this->assertStringContainsString($video->getUrl(), $html);
            $this->assertStringContainsString($cover->getUrl(), $html);
        }
    }

    public function test_callback_and_lens_coating_render_through_the_registry(): void
    {
        $registry = app(BlockRegistry::class);
        $this->assertTrue($registry->has(BlockType::BANNER_SPECIALIST_CALLBACK));
        $this->assertTrue($registry->has(BlockType::CARD_COATING));

        $callback = $this->page->blocks()->create([
            'title' => 'Запись или обратный звонок',
            'type' => BlockType::BANNER_SPECIALIST_CALLBACK,
        ]);
        $this->saveExistingBlock($callback);
        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $callback->refresh()]);
        $this->assertStringContainsString("openBookingWidgetV3('otpravka-formy')", $html);
        $this->assertStringContainsString("showCallbackFormNew(null, 'otpravka-formy')", $html);

        $payload = ['coating' => [[
            'title' => 'Покрытие Stellest',
            'list' => '<p>Описание покрытия</p>',
            'colors' => true,
            'btn_text' => 'Выбрать покрытие',
        ]]];
        $this->createBlock([
            'title' => 'Покрытия линз',
            'type' => BlockType::CARD_COATING->value,
            'payload' => $payload,
        ]);
        $coating = $this->page->blocks()->where('type', BlockType::CARD_COATING->value)->sole();
        $this->assertSame($payload, $coating->payload);
        $this->saveExistingBlock($coating);
        $this->assertSame($payload, $coating->refresh()->payload);
        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $coating]);
        $this->assertStringContainsString('Покрытие Stellest', $html);
        $this->assertStringContainsString('Описание покрытия', $html);
        $this->assertStringContainsString('Цвет линзы на выбор', $html);
        $this->assertStringContainsString('Выбрать покрытие', $html);
    }

    public function test_video_carousel_preserves_saved_video_order_and_renders_sources(): void
    {
        $this->assertTrue(app(BlockRegistry::class)->has(BlockType::VIDEO_CAROUSEL));
        $this->createBlock([
            'title' => 'Видео карусель',
            'type' => BlockType::VIDEO_CAROUSEL->value,
        ], ['data.videos' => [
            UploadedFile::fake()->create('one.mp4', 10, 'video/mp4'),
            UploadedFile::fake()->create('two.mp4', 10, 'video/mp4'),
        ]]);
        $block = $this->page->blocks()->where('type', BlockType::VIDEO_CAROUSEL->value)->sole();
        $videos = $block->getMedia('videos');
        $this->assertCount(2, $videos);

        [$first, $second] = $videos;
        $first->update(['order_column' => 2]);
        $second->update(['order_column' => 1]);
        $block->refresh();
        $this->saveExistingBlock($block);
        $block->refresh();
        $this->assertSame([$second->id, $first->id], $block->getMedia('videos')->pluck('id')->all());

        $html = Blade::render('<x-block :block="$block" breadcrumbs-title="" page-title="" page-description="" />', ['block' => $block]);
        $this->assertStringContainsString($first->getUrl(), $html);
        $this->assertStringContainsString($second->getUrl(), $html);
        $this->assertLessThan(strpos($html, $first->getUrl()), strpos($html, $second->getUrl()));
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
