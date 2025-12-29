<?php

namespace App\Filament\Resources;

use App\Enums\BlockBackgroundType;
use App\Enums\BlockType;
use App\Enums\PageType;
use App\Filament\Resources\BlockResource\Pages;
use App\Models\Block;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Review;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Блоки';

    protected static ?string $label = 'Блок';

    protected static ?string $pluralLabel = 'Блоки';

    protected static ?string $navigationIcon = 'heroicon-s-cube';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->columnSpan('full')
                        ->required(),

                    Forms\Components\Toggle::make('settings.title_hidden')
                        ->columnSpanFull()
                        ->label('Не отображать заголовок')
                        ->afterStateUpdated(
                            fn(Forms\Set $set, bool $state) => $set(
                                'settings.show_page_title',
                                false
                            )
                        )
                        ->reactive(),

                    Forms\Components\TextInput::make('anchor')
                        ->label('Идентификатор (якорь)')
                        ->prefix('#'),

                    Forms\Components\Select::make('page_id')
                        ->label('Страница')
                        ->required()
                        ->relationship('page', 'title', fn($query) => $query->orderBy('id')),

                    Forms\Components\Select::make('cities')
                        ->label('Города')
                        ->multiple()
                        ->relationship('cities', 'name')
                        ->preload()
                        ->helperText('Если пусто - доступна везде'),

                    Forms\Components\Select::make('type')
                        ->columnSpanFull()
                        ->label('Тип')
                        ->options(BlockType::toArray())
                        ->default(BlockType::HTML->value)
                        ->reactive(),

                    Forms\Components\TextInput::make('payload.image_title')
                        ->columnSpanFull()
                        ->label('Заголовок фото')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::WELCOME, BlockType::PICTURE, BlockType::ADVANTAGES]
                            )
                        ),
                    Forms\Components\TextInput::make('payload.image_subtitle')
                        ->columnSpanFull()
                        ->label('Подзаголовок фото')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::WELCOME
                        ),


                    Forms\Components\Select::make('payload.doctors')
                        ->multiple()
                        ->columnSpanFull()
                        ->label('Специалисты')
                        ->options(Doctor::query()->pluck('surname', 'id'))
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::DOCTORS_ALT]
                            )
                        ),

                    Forms\Components\Select::make('payload.reviews')
                        ->multiple()
                        ->columnSpanFull()
                        ->options(Review::query()->pluck('name', 'id'))
                        ->label('Отзывы')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::REVIEWS_ALT
                        ),

                    Forms\Components\Select::make('payload.author')
                        ->columnSpanFull()
                        ->label('Специалист')
                        ->options(Doctor::query()->pluck('surname', 'id'))
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::AUTHOR
                        ),

                    Forms\Components\TextInput::make('payload.url')
                        ->columnSpanFull()
                        ->label('Ссылка')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::AUTHOR
                        ),

                    Forms\Components\TextInput::make('payload.theme')
                        ->columnSpanFull()
                        ->label('Тема статьи')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::AUTHOR
                        ),

                    Forms\Components\Select::make('payload.service')
                        ->columnSpanFull()
                        ->label('Услуга')
                        ->options(
                            Service::query()
                                ->pluck('title', 'uuid')
                        )
                        ->required(
                            fn(Forms\Get $get) => !in_array($get('type'), [
                                BlockType::PRICE_LIST->value,
                            ])
                        )
                        ->hidden(
                            fn(Forms\Get $get) => !in_array($get('type'), [
                                BlockType::PRICE_LIST->value,
                            ])
                        ),

                    Forms\Components\RichEditor::make('body_html')
                        ->label('Текст')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::HTML, BlockType::TEXT_WITH_IMAGE, BlockType::TEXT_SUBDUED, BlockType::WELCOME, BlockType::POST_TEXT,]
                            )
                        )
                        ->columnSpan('full'),

                    Forms\Components\Select::make('payload.bg_block')
                        ->label('Цвет блока')
                        ->options([
                            'bg-surface' => 'Белый',
                            'bg-surface-subdued' => 'Серый',
                            'bg-action-primary-light' => 'Бежевый',
                        ])
                        ->default('bg-surface')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::POST_TEXT,]
                            )
                        ),

                    Forms\Components\Textarea::make('body_html')
                        ->label(fn(Forms\Get $get) => $get('type') === BlockType::HTML_CODE ? 'HTML-код' : 'Текст')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::HTML_CODE,
                                BlockType::TEXT_WITH_IMAGE_ALT,
                            ]))
                        ->columnSpan('full'),

                    Forms\Components\Repeater::make('payload.tags')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Название')
                                ->required(),
                            Forms\Components\TextInput::make('link')
                                ->label('Ссылка')
                                ->required(),
                        ])
                        ->required(
                            fn(Forms\Get $get) => BlockType::from($get('type')) ==
                                BlockType::TAGS
                        )
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TAGS
                        ),

                    Forms\Components\RichEditor::make('payload.enter_text')
                        ->columnSpanFull()
                        ->label('Начальный текст')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !==
                                BlockType::GUARANTEE
                        ),
                    Forms\Components\Repeater::make('payload.guarantee')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->required(),
                            Forms\Components\Textarea::make('text')
                                ->label('Текст')
                                ->required(),
                        ])
                        ->label('Гарантии')
                        ->required(
                            fn(Forms\Get $get) => BlockType::from($get('type')) ==
                                BlockType::GUARANTEE
                        )
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::GUARANTEE
                        ),

                    Forms\Components\Section::make([
                        Forms\Components\Toggle::make('payload.is_opening')
                            ->label('Открывающийся')
                            ->default(true),
                        Forms\Components\Toggle::make('payload.is_rounded')
                            ->label('Скруглить углы')
                            ->default(true),
                    ])
                        ->columns(4)
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_BLOCKS
                        ),

                    Forms\Components\Repeater::make('payload.text_content')
                        ->label('Текстовые блоки')
                        ->schema([
                            Forms\Components\Section::make([
                                Forms\Components\Toggle::make('is_grid')
                                    ->label('Сетка')
                                    ->reactive()
                                    ->default(false)
                            ])->columns(4),
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок'),
                            Forms\Components\TextInput::make('subtitle')
                                ->label('Подаголовок'),
                            Forms\Components\Section::make([
                                Forms\Components\RichEditor::make('body_html')
                                    ->label('Текст'),
                                Forms\Components\FileUpload::make('picture')
                                    ->label('Изображение'),
                            ]),

                            Forms\Components\Repeater::make('grid_blocks')
                                ->label('Сетка блоков')
                                ->reactive()
                                ->schema([
                                    Forms\Components\RichEditor::make('body_html')
                                        ->label('Текст'),
                                    Forms\Components\Select::make('col_count')
                                        ->label('Занимает колонок')
                                        ->options([
                                            '1' => '1',
                                            '2' => '2',
                                            '3' => '3',
                                            '4' => '4',
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->hidden(
                                    fn(Forms\Get $get) => $get('is_grid') === false
                                ),

                            Forms\Components\TextInput::make('cols_count')
                                ->label('Кол-во колонок')
                                ->hidden(
                                    fn(Forms\Get $get) => $get('is_grid') === false
                                ),

                            Forms\Components\Select::make('classes')
                                ->label('Классы')
                                ->multiple()
                                ->options([
                                    'gray md:rounded-xl bg-surface-subdued p-4 md:py-10 md:px-10 -mx-5 md:mx-0' => 'Серый фон',
                                    'with-image-block' => 'Блок с изображением',
                                    'text-center' => 'Текст по центру'
                                ])
                        ])
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_BLOCKS
                        ),

                    SpatieMediaLibraryFileUpload::make('default')
                        ->label('Изображение')
                        ->imageEditor()
                        ->responsiveImages()
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')),
                                [
                                    BlockType::TEXT_WITH_IMAGE,
                                    BlockType::WELCOME,
                                    BlockType::TEXT_WITH_IMAGE_ALT,
                                    BlockType::PICTURE,
                                    BlockType::POST_TEXT,
                                    BlockType::LIST_WITH_IMAGE,
                                ])
                        ),

                    Forms\Components\Select::make('payload.classes')
                        ->label('Классы')
                        ->multiple()
                        ->options([
                            'text-center mx-auto' => 'По центру',
                            'rounded-xl md:rounded-20' => 'Скругление',
                        ])
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')),
                                [
                                    BlockType::PICTURE,
                                ])
                        ),

                    Forms\Components\TextInput::make('payload.width')
                        ->label('Ширина')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::PICTURE
                            ])
                        ),

                    Forms\Components\TextInput::make('payload.subtitle')
                        ->label('Подзаголовок')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::CALL_TO_ACTION,
                            ])
                        ),

                    Forms\Components\TextInput::make('payload.link')
                        ->columnSpanFull()
                        ->label('Ссылка на страницу')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::TEXT_WITH_IMAGE]
                            )
                        ),

                    Forms\Components\Toggle::make('payload.reverse')
                        ->label('Перевернуть')
                        ->default(false)
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::TEXT_WITH_IMAGE,
                            ])
                        ),

                    Forms\Components\Toggle::make('payload.add_fox')
                        ->label('Добавить лисенка')
                        ->default(true)
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::TEXT_WITH_IMAGE,
                                BlockType::NIGHT_LENSES_SELECTION,
                                BlockType::SELECT_LENSES_SELECTION,
                                BlockType::CALL_TO_ACTION,
                            ])
                        ),

                    Forms\Components\Toggle::make('payload.add_fox2')
                        ->label('Добавить 2го лисенка')
                        ->default(true)
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::CALL_TO_ACTION,
                            ])
                        ),

                    SpatieMediaLibraryFileUpload::make('video')
                        ->collection('video')
                        ->label('Видео')
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [BlockType::VIDEO, BlockType::VIDEO_NEW])
                        ),

                    SpatieMediaLibraryFileUpload::make('cover')
                        ->collection('cover')
                        ->label('Обложка видео')
                        ->imageEditor()
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [BlockType::VIDEO, BlockType::VIDEO_NEW])

                        ),

                    Forms\Components\TextInput::make('payload.var_1_title')
                        ->label('Вариант 1 заголовок')
                        ->required()
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_WITH_CHART
                        ),
                    Forms\Components\Textarea::make('payload.var_1_text')
                        ->label('Вариант 1 текст')
                        ->required()
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_WITH_CHART
                        ),
                    Forms\Components\TextInput::make('payload.var_2_title')
                        ->label('Вариант 2 заголовок')
                        ->required()
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_WITH_CHART
                        ),
                    Forms\Components\Textarea::make('payload.var_2_text')
                        ->label('Вариант 2 текст')
                        ->required()
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::TEXT_WITH_CHART
                        ),
                    SpatieMediaLibraryFileUpload::make('payload.bg_chart')
                        ->label('Изображение графика')
                        ->collection('bg_chart')
                        ->imageEditor()
                        ->responsiveImages()
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [BlockType::TEXT_WITH_CHART, BlockType::ADVANTAGES]),

                        ),
                ]),

                Forms\Components\Repeater::make('payload.faq')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->label('Вопрос')
                            ->required(),

                        Forms\Components\RichEditor::make('answer_html')
                            ->label('Ответ')
                            ->required(),

                        Forms\Components\FileUpload::make('icon')
                            ->label('Иконка'),
                    ])
                    ->required(
                        fn(Forms\Get $get) => BlockType::from($get('type')) ==
                            BlockType::FAQ
                    )
                    ->columnSpanFull()
                    ->hidden(
                        fn(Forms\Get $get) => BlockType::from($get('type')) !=
                            BlockType::FAQ
                    ),

                Forms\Components\Repeater::make('payload.advantages')
                    ->schema([
                        Forms\Components\FileUpload::make('icon')
                            ->label('Иконка'),
                        Forms\Components\TextInput::make('alt_image')
                            ->label('Alt для изображения')
                            ->required(),
                        Forms\Components\Textarea::make('text')
                            ->label('Текст')
                            ->required(),
                    ])
                    ->required(
                        fn(Forms\Get $get) => BlockType::from($get('type')) ==
                            BlockType::ADVANTAGES
                    )
                    ->columnSpanFull()
                    ->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::ADVANTAGES,
                            BlockType::DETAILS,
                        ])
                    ),

                Forms\Components\Repeater::make('payload.order')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required(),
                        Forms\Components\RichEditor::make('text')
                            ->label('Текст')
                            ->required(),
                    ])
                    ->label('Карточка')
                    ->columnSpanFull()
                    ->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::HOW_TO_ORDER,
                            BlockType::NIGHT_LENSES_SELECTION,
                            BlockType::SELECT_LENSES_SELECTION,
                        ])
                    ),

                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('payload.service_hero_title')
                        ->columnSpanFull()
                        ->label('Заголовок')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                                BlockType::BANNER_CORRECTION,
                                BlockType::BANNER_MYOPIA,
                            ])),
                    Forms\Components\TextInput::make('payload.service_hero_subtitle')
                        ->columnSpanFull()
                        ->label('Подзаголовок')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                            ])),
                    Forms\Components\TextInput::make('payload.old_price')
                        ->columnSpanFull()
                        ->label('Старая цена')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                            ])),
                    Forms\Components\TextInput::make('payload.price')
                        ->columnSpanFull()
                        ->label('Цена')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                            ])),
                    Forms\Components\Textarea::make('payload.service_hero_text')
                        ->columnSpanFull()
                        ->label('Текст')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                                BlockType::BANNER_CORRECTION,
                                BlockType::BANNER_MYOPIA,
                            ])),
                    SpatieMediaLibraryFileUpload::make('bg')
                        ->label('Изображение')
                        ->collection('bg')
                        ->imageEditor()
                        ->responsiveImages()
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                                BlockType::BANNER_CORRECTION,
                                BlockType::BANNER_MYOPIA,
                                BlockType::DETAILS,
                            ])),
                    SpatieMediaLibraryFileUpload::make('pic')
                        ->label('Изображение (для мобильных)')
                        ->collection('pic')
                        ->responsiveImages()
                        ->imageEditor()
                        ->openable()
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_WITH_BUTTON,
                                BlockType::BANNER_NIGHT_LENSES,
                                BlockType::BANNER_APPOINTMENT,
                                BlockType::BANNER_CORRECTION,
                                BlockType::BANNER_MYOPIA,
                                BlockType::DETAILS,
                            ]))
                ]),

                Forms\Components\Section::make([
                    Forms\Components\Section::make([
                        Forms\Components\TextInput::make('payload.count_visible')
                            ->label('Сколько элементов показывать')
                            ->columnSpan('3'),
                        Forms\Components\Toggle::make('payload.is_blog')
                            ->label('Блог')
                            ->default(false)
                            ->columnSpan('3')
                            ->reactive(),
                    ])->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::CARDS_SLIDER, BlockType::ADVANTAGES_SLIDER
                        ])
                    ),

                    Forms\Components\Repeater::make('images')
                        ->label('Изображения')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->hiddenLabel()
                                ->columnSpan('full')
                                ->default(Str::uuid()->toString())
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $state) {
                                    if (empty($state)) {
                                        $component->state(Str::uuid()->toString());
                                    }
                                })
                                ->required()
                                ->reactive()
                                ->extraAttributes(['class' => 'hidden'])
                                ->readOnly(),

                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\Textarea::make('text')
                                ->label('Текст')
                                ->columnSpan('full')
                                ->hidden(
                                    fn(Forms\Get $get) => !in_array(BlockType::from($get('../../type')), [
                                        BlockType::ADVANTAGES_SLIDER
                                    ])
                                ),

                            Forms\Components\Toggle::make('show_callback_button')
                                ->label(
                                    'При нажатии открывать форму "Записаться на прием"'
                                )
                                ->columnSpan('full')
                                ->reactive()
                                ->hidden(
                                    fn(Forms\Get $get) => BlockType::from(
                                            $get('../../type')
                                        ) !== BlockType::CAROUSEL
                                ),

                            Forms\Components\TextInput::make('url')
                                ->label('Ссылка')
                                ->columnSpan('full')
                                ->reactive()
                                ->hidden(
                                    fn(Forms\Get $get) => !in_array(BlockType::from($get('../../type')), [
                                            BlockType::CAROUSEL, BlockType::CARDS_SLIDER, BlockType::BANNERS_GRID
                                        ]) ||
                                        $get('show_callback_button') === true
                                ),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('uuid'))
                                ->label('Изображение')
                                ->responsiveImages()
                                ->required(),

                            SpatieMediaLibraryFileUpload::make('mobile_image')
                                ->collection(
                                    fn(Forms\Get $get) => 'mobile_' . $get('uuid')
                                )
                                ->label('Изображение для мобильных устройств')
                                ->responsiveImages()
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../type')), [
                                        BlockType::ADVANTAGES_SLIDER
                                    ])
                                ),
                        ])
                        ->hidden(
                            fn(Forms\Get $get) => $get('payload.is_blog') === true
                        )
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::CAROUSEL,
                        BlockType::PHOTO,
                        BlockType::CARDS_SLIDER,
                        BlockType::BANNERS_GRID,
                        BlockType::ADVANTAGES_SLIDER,
                    ])
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.utp')
                        ->label('УТП')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->hiddenLabel()
                                ->columnSpan('full')
                                ->default(
                                    fn(Forms\Get $get) => $get('uuid') ??
                                        Str::uuid()->toString()
                                )
                                ->required()
                                ->reactive()
                                ->extraAttributes(['class' => 'hidden']),

                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\Textarea::make('body_html')
                                ->label('Текст')
                                ->columnSpan('full')
                                ->required(),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('uuid'))
                                ->label('Изображение')
                                ->responsiveImages()
                                ->required(),
                        ]),
                ])->hidden(fn(Forms\Get $get) => BlockType::from($get('type')) != BlockType::UTP),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.coating')
                        ->label('Покрытие')
                        ->schema([

                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\RichEditor::make('list')
                                ->label('Выгоды')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\Toggle::make('colors')
                                ->label('Показать цвета')
                                ->default(false)
                                ->reactive()
                                ->columnSpan('full'),

                            Forms\Components\TextInput::make('btn_text')
                                ->label('Текст кнопки')
                                ->columnSpan('full')
                                ->required(),
                        ]),
                ])->hidden(fn(Forms\Get $get) => BlockType::from($get('type')) != BlockType::CARD_COATING),

                Forms\Components\Section::make([
                    SpatieMediaLibraryFileUpload::make('videos')
                        ->collection('videos')
                        ->label('Вертикальные видео')
                        ->multiple()
                        ->required(),
                ])
                    ->hidden(fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [BlockType::VIDEO_CAROUSEL])),


                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('payload.count_col')
                        ->label('Кол-во колонок')
                        ->default(1),
                    Forms\Components\Repeater::make('payload.services')
                        ->label('Элементы')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\TextInput::make('subtitle')
                                ->label('Подзаголовок')
                                ->columnSpan('full'),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpan('full'),

                            Forms\Components\Toggle::make('dark_card')
                                ->label('Темная карточка')
                                ->columnSpan('full'),

                            Forms\Components\Select::make('services')
                                ->columnSpanFull()
                                ->options(Page::query()->where('type', '=', PageType::Services)->where('active', '=', 1)->pluck('title', 'handle'))
                                ->label('Услуга'),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get(
                                        'media_collection'
                                    ) ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->extraAttributes(['class' => 'hidden'])
                                ->required(),


                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(
                                    fn(Forms\Get $get) => $get('media_collection')
                                )
                                ->label('Изображение')
                                ->responsiveImages()
                                ->required(),
                        ]),
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::SERVICES_BLOCK, BlockType::CARDS_FEATURE
                    ])
                ),

                Forms\Components\TextInput::make('payload.count_column')
                    ->label('Кол-во колонок')
                    ->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::SEVERAL_COLS,
                        ])
                    ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.elements')
                        ->label('Элементы')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\TextInput::make('subtitle')
                                ->label('Подзаголовок')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_BORDER,
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->columnSpan('full'),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpan('full'),

                            Forms\Components\Toggle::make('do_not_show_in_modal')
                                ->label('Не отображать во всплывающем окне')
                                ->reactive()
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_ITEM_ROW,
                                        BlockType::CARDS_BORDER,
                                        BlockType::NIGHT_LENSES_PICTURES,
                                        BlockType::SEVERAL_COLS,
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->columnSpan('full'),

                            Forms\Components\Toggle::make('has_price')
                                ->label('Отображать ссылку на общий прайс')
                                ->reactive()
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_ITEM_ROW,
                                        BlockType::CARDS_BORDER,
                                        BlockType::NIGHT_LENSES_PICTURES,
                                        BlockType::SEVERAL_COLS,
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->columnSpan('full'),

                            Forms\Components\Select::make('uuid')
                                ->columnSpanFull()
                                ->label('Услуга')
                                ->options(Service::query()->pluck('title', 'uuid'))
                                ->required(fn(Forms\Get $get) => $get('has_price'))
                                ->hidden(fn(Forms\Get $get) => !$get('has_price')),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get(
                                        'media_collection'
                                    ) ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->required()
                                ->extraAttributes(['class' => 'hidden']),

                            Forms\Components\Toggle::make('has_an_appointment')
                                ->label('Отображать кнопку «Записаться на приём»')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_ITEM_ROW,
                                        BlockType::CARDS_BORDER,
                                        BlockType::NIGHT_LENSES_PICTURES,
                                        BlockType::SEVERAL_COLS,
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->columnSpan('full'),

                            Forms\Components\Toggle::make('work')
                                ->label('Белые карточки на прозрачном фоне')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_ITEM_ROW,
                                        BlockType::CARDS_BORDER,
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->columnSpan('full'),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(
                                    fn(Forms\Get $get) => $get('media_collection')
                                )
                                ->label('Изображение')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::LIST_WITH_IMAGE,
                                    ])
                                )
                                ->responsiveImages(),

                            ColorPicker::make('card_color')
                                ->label('Цвет карточки')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::CARDS_ITEM_ROW,
                                    ])
                                )
                                ->rgba(),
                        ]),
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::ELEMENTS_ITEM_COLUMN,
                        BlockType::ELEMENTS_ITEM_ROW,
                        BlockType::CARDS_ITEM_ROW,
                        BlockType::CARDS_BORDER,
                        BlockType::NIGHT_LENSES_PICTURES,
                        BlockType::SEVERAL_COLS,
                        BlockType::LIST_WITH_IMAGE,
                    ])
                ),

                Forms\Components\Section::make([
                    Forms\Components\Textarea::make('payload.html')
                        ->label('Текст HTML')
                        ->columnSpan('full')
                        ->hidden(
                            fn(Forms\Get $get) => in_array(BlockType::from($get('type')), [
                                BlockType::LIST_TEXT_WITH_LINK,
                            ])
                        ),
                    Forms\Components\Repeater::make('payload.grid')
                        ->label('Сетка карточек')
                        ->schema([
                            Forms\Components\TextInput::make('text')
                                ->label('Текст')
                                ->columnSpan('full'),
                            Forms\Components\FileUpload::make('document')
                                ->label('Документ')
                                ->reactive()
                                ->directory('docs')
                                ->hidden(
                                    fn(Forms\Get $get) => !empty($get('link'))
                                ),
                            Forms\Components\TextInput::make('link')
                                ->label('Ссылка')
                                ->reactive()
                                ->columnSpan('full')
                                ->hidden(
                                    fn(Forms\Get $get) => in_array(BlockType::from($get('../../../type')), [
                                        BlockType::UNIVERSAL_TEXT_BLOCK,
                                    ]) || !empty($get('document'))
                                ),
                        ])
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::UNIVERSAL_TEXT_BLOCK, BlockType::LIST_TEXT_WITH_LINK
                    ])
                ),

                Forms\Components\Section::make([
                    Forms\Components\FileUpload::make('payload.image')
                        ->label('Изображение')
                        ->directory('corgi'),
                    Forms\Components\Repeater::make('payload.contacts')
                        ->label('Сетка контактов')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Название организации'),
                            Forms\Components\RichEditor::make('info')
                                ->label('Информация')
                        ])
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::GRID_CONTACTS,
                    ])
                ),


                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.elements')->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->columnSpan('full')
                            ->required(),

                        Forms\Components\Textarea::make('body_html')
                            ->label('Текст')
                            ->columnSpan('full')
                            ->required(),
                    ])
                ])->visible(fn(Forms\Get $get) => BlockType::from($get('type')) === BlockType::POINTS),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.elements')
                        ->label('Элементы')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpan('full')
                                ->required(),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get(
                                        'media_collection'
                                    ) ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->required()
                                ->extraAttributes(['class' => 'hidden']),


                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(
                                    fn(Forms\Get $get) => $get('media_collection')
                                )
                                ->label('Изображение')
                                ->responsiveImages()
                                ->required(),
                        ]),
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::GRID_CAROUSEL,
                    ])
                ),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Toggle::make('settings.show_page_title')
                            ->helperText(
                                'В остальных блоках на этой странице заголовок страницы h1 показываться не будет. Заголвок блока также не будет отображаться.'
                            )
                            ->label('Отображать заголовок страницы h1 в этом блоке')
                            ->afterStateUpdated(
                                fn(Forms\Set $set, bool $state) => $state === true
                                    ? $set('settings.title_hidden', true)
                                    : $set('settings.title_hidden', false)
                            )
                            ->hidden(
                                fn(Forms\Get $get) => in_array(BlockType::from($get('type')), [BlockType::LICENSES->value, BlockType::GUARANTEE])
                            )
                            ->reactive(),

                        Forms\Components\Toggle::make('settings.breadcrumbs')
                            ->helperText(
                                'В остальных блоках на этой странице хлебные крошки показываться не будут'
                            )
                            ->label('Отображать хлебные крошки в этом блоке')
                            ->hidden(
                                fn(Forms\Get $get) => in_array(BlockType::from($get('type')), [BlockType::LICENSES->value, BlockType::GUARANTEE])
                            ),

                        Forms\Components\Toggle::make('settings.show_on_mobile')
                            ->default(true)
                            ->label('Отображать на мобильных устройствах'),

                        Forms\Components\Toggle::make('settings.hide_on_desctop')
                            ->default(false)
                            ->label('Отключить на Десктопе'),

                        Forms\Components\Select::make('settings.background')
                            ->columnSpanFull()
                            ->label('Фон блока')
                            ->options(BlockBackgroundType::toArray())
                            ->default(BlockBackgroundType::SURFACE->value),

                        Forms\Components\Toggle::make('settings.remove_top_padding')
                            ->columnSpanFull()
                            ->label('Убрать отступ сверху'),

                        Forms\Components\Toggle::make(
                            'settings.remove_bottom_padding'
                        )
                            ->columnSpanFull()
                            ->label('Убрать отступ снизу'),
                    ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        $originalReplicatePageId = null;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Заголовок'),
                Tables\Columns\TextColumn::make('page.title')->label(
                    'Страница'
                ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page')
                    ->label('Страница')
                    ->relationship('page', 'title'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип блока')
                    ->options(BlockType::toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->form([
                        Forms\Components\Select::make('page_id')
                            ->label('Страница')
                            ->relationship('page', 'title')
                            ->required(),
                    ])
                    ->mutateRecordDataUsing(function ($data) {
                        Session::put(
                            'original_replicate_page_id',
                            $data['page_id']
                        );

                        return $data;
                    })
                    ->beforeReplicaSaved(function (
                        Block $record,
                        Block $replica
                    ): void {
                        $media = $record->media;

                        $media->each(function ($image) use ($replica) {
                            $replica
                                ->addMediaFromStream($image->stream())
                                ->usingFileName($image->file_name)
                                ->toMediaCollection($image->collection_name);
                        });

                        //                        collect($replica->images)->each(function ($item) use ($replica, $record) {
                        //                            $image = $record->getFirstMedia($item['uuid']);
                        //                            $replica
                        //                                ->addMediaFromStream($image->stream())
                        //                                ->usingFileName($image->file_name)
                        //                                ->toMediaCollection($item['uuid']);
                        //                        });

                        $record
                            ->forceFill([
                                'page_id' => Session::get(
                                    'original_replicate_page_id'
                                ),
                            ])
                            ->save();

                        Session::forget('original_replicate_page_id');
                    }),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            //            RelationManagers\ElementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlocks::route('/'),
            'create' => Pages\CreateBlock::route('/create'),
            'edit' => Pages\EditBlock::route('/{record}/edit'),
        ];
    }
}
