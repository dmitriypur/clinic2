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
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
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

    protected static function getDoctorOptionsQuery(?array $cityIds = null, ?string $search = null)
    {
        $cityIds = collect($cityIds ?? [])
            ->filter()
            ->values();

        return Doctor::query()
            ->publiclyVisible()
            ->when($cityIds->isNotEmpty(), function ($query) use ($cityIds) {
                $query->where(function ($doctorQuery) use ($cityIds) {
                    $doctorQuery
                        ->whereHas('cities', fn($cityQuery) => $cityQuery->whereIn('cities.id', $cityIds))
                        ->orDoesntHave('cities');
                });
            })
            ->when(
                filled($search),
                fn($query) => $query->where(function ($doctorQuery) use ($search) {
                    $doctorQuery
                        ->where('surname', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(surname, ' ', name) LIKE ?", ["%{$search}%"]);
                })
            )
            ->orderBy('surname')
            ->orderBy('name');
    }

    protected static function getDoctorLabels(array $doctorIds): array
    {
        return Doctor::query()
            ->whereIn('id', $doctorIds)
            ->orderBy('surname')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
            ->all();
    }

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

                    Forms\Components\Select::make('page_type_filter')
                        ->label('Фильтр типа страниц')
                        ->options(PageType::toArray())
                        ->placeholder('Все типы')
                        ->dehydrated(false)
                        ->live()
                        ->hidden(fn($livewire) => $livewire instanceof RelationManager && $livewire->getOwnerRecord() instanceof Page),

                    Forms\Components\Select::make('page_id')
                        ->label('Страница')
                        ->required()
                        ->options(function (Forms\Get $get) {
                            $type = $get('page_type_filter');

                            return Page::query()
                                ->when($type !== null && $type !== '', fn($query) => $query->where('type', $type))
                                ->orderBy('id')
                                ->pluck('title', 'id');
                        })
                        ->searchable()
                        ->hidden(fn($livewire) => $livewire instanceof RelationManager && $livewire->getOwnerRecord() instanceof Page)
                        ->default(fn($livewire) => $livewire instanceof RelationManager && $livewire->getOwnerRecord() instanceof Page
                            ? $livewire->getOwnerRecord()->id
                            : null)
                        ->dehydrated(),

                    Forms\Components\Select::make('cities')
                        ->label('Доступность в городах')
                        ->relationship('cities', 'name')
                        ->multiple()
                        ->preload()
                        ->live()
                        ->helperText('Если пусто - блок доступен во всех городах'),

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


                    Forms\Components\Select::make('payload.excluded_doctors')
                        ->multiple()
                        ->columnSpanFull()
                        ->label('Исключить специалистов')
                        ->options(fn(Forms\Get $get): array => static::getDoctorOptionsQuery($get('cities'))
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                            ->all())
                        ->searchable()
                        ->getSearchResultsUsing(fn(string $search, Forms\Get $get): array => static::getDoctorOptionsQuery($get('cities'), $search)
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                            ->all())
                        ->getOptionLabelsUsing(fn(array $values): array => static::getDoctorLabels($values))
                        ->helperText('Если поле пустое, блок покажет всех активных врачей выбранного города. В списке доступны врачи выбранных городов и врачи без привязки к городу.')
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
                            fn(Forms\Get $get) => !in_array(
                                BlockType::from($get('type')),
                                [BlockType::AUTHOR, BlockType::EXPERT_OPINION]
                            )
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
                            fn(Forms\Get $get) => in_array($get('type'), [
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
                                [BlockType::HTML, BlockType::TEXT_WITH_IMAGE, BlockType::TEXT_WITH_IMAGE_NEW,
                                BlockType::TEXT_SUBDUED, BlockType::WELCOME, BlockType::POST_TEXT, BlockType::APPARATUS_DISEASES, BlockType::APPARATUS_METHODS, BlockType::APPARATUS_CONTRAINDICATIONS, BlockType::DIAGNOSTIC_METHODS, BlockType::TREATMENT_METHODS, BlockType::EXPERT_OPINION,]
                            )
                        )
                        ->columnSpan('full'),

                    Forms\Components\Section::make([
                        Forms\Components\TextInput::make('payload.fio_expert')
                            ->label('Фио эксперта'),
                        Forms\Components\TextInput::make('payload.position_expert')
                            ->label('Должность эксперта'),
                    ])
                        ->columns(2)
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::from($get('type')) !=
                                BlockType::EXPERT_OPINION
                        ),

                    Forms\Components\Select::make('payload.bg_block')
                        ->label('Цвет блока')
                        ->options([
                            'bg-surface' => 'Белый',
                            'bg-surface-subdued' => 'Серый',
                            'bg-action-primary-light' => 'Бежевый',
                        ])
                        ->default('bg-surface')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::tryFrom((string) $get('type')) !== BlockType::POST_TEXT
                        ),
                    Forms\Components\Select::make('payload.image_position')
                        ->label('Позиция изображения')
                        ->options([
                            'none' => 'Без изображения',
                            'right' => 'Справа',
                            'left' => 'Слева',
                        ])
                        ->default('right')
                        ->hidden(
                            fn(Forms\Get $get) => BlockType::tryFrom((string) $get('type')) !== BlockType::POST_TEXT
                        ),

                    Forms\Components\Textarea::make('body_html')
                        ->label(fn(Forms\Get $get) => $get('type') === BlockType::HTML_CODE ? 'HTML-код' : 'Текст')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::tryFrom((string) $get('type')), [
                                BlockType::HTML_CODE,
                                BlockType::TEXT_WITH_IMAGE_ALT,
                            ], true))
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
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::TAGS,
                                BlockType::TAGS_NEW,
                            ])
                        )
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::TAGS,
                                BlockType::TAGS_NEW,
                            ])),

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
                                BlockType::TEXT_BLOCKS,
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
                                    BlockType::TEXT_WITH_IMAGE_NEW,
                                    BlockType::WELCOME,
                                    BlockType::TEXT_WITH_IMAGE_ALT,
                                    BlockType::PICTURE,
                                    BlockType::POST_TEXT,
                                    BlockType::LIST_WITH_IMAGE,
                                    BlockType::APPARATUS_DISEASES,
                                    BlockType::DIAGNOSTIC_METHODS,
                                    BlockType::EXPERT_OPINION,
                                ])
                        ),

                    Forms\Components\Textarea::make('payload.cards_intro')
                        ->label('Подзаголовок перед карточками')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::DIAGNOSTIC_METHODS,
                                BlockType::TREATMENT_METHODS,
                            ], true)
                        )
                        ->columnSpanFull(),

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

                    Forms\Components\TextInput::make('payload.btn_text')
                        ->columnSpanFull()
                        ->label('Текст кнопки')
                        ->hidden(
                            fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                                BlockType::BANNER_SELECTION_GLASSES,
                                BlockType::BANNER_APPARATUS_HERO,
                                BlockType::APPARATUS_METHODS,
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
                    Forms\Components\Repeater::make('payload.sections')
                        ->label('Секции')
                        ->reorderable(false)
                        ->addable(false)
                        ->deletable(false)
                        ->defaultItems(2)
                        ->minItems(2)
                        ->maxItems(2)
                        ->helperText('Первая секция показывается сверху, вторая — ниже.')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\TextInput::make('link')
                                ->label('Ссылка')
                                ->columnSpanFull()
                                ->url(),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->required()
                                ->extraAttributes(['class' => 'hidden']),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('media_collection'))
                                ->label('Изображение')
                                ->imageEditor()
                                ->responsiveImages()
                                ->required(),
                        ]),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::APPARATUS_TREATMENT
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
                                BlockType::BANNER_SELECTION_GLASSES,
                                BlockType::BANNER_APPARATUS_HERO,
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
                                BlockType::BANNER_SELECTION_GLASSES,
                                BlockType::BANNER_APPARATUS_HERO,
                            ])),
                    SpatieMediaLibraryFileUpload::make('bg')
                        ->label('Фон (desktop)')
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
                                BlockType::BANNER_SELECTION_GLASSES,
                                BlockType::BANNER_APPARATUS_HERO,
                            ])),
                    SpatieMediaLibraryFileUpload::make('pic')
                        ->label('Изображение (mobile)')
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
                                BlockType::BANNER_SELECTION_GLASSES,
                                BlockType::BANNER_APPARATUS_HERO,
                            ])),
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
                        Forms\Components\Toggle::make('payload.is_button')
                            ->label('Кнопка вместо ссылки')
                            ->default(false)
                            ->columnSpan('3')
                            ->reactive(),
                    ])->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::CARDS_SLIDER, BlockType::ADVANTAGES_SLIDER
                        ])
                    ),

                    Forms\Components\Repeater::make('images')
                        ->label('Карточка')
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
                                        BlockType::ADVANTAGES_SLIDER, BlockType::CARDS_SLIDER
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
                                            BlockType::CAROUSEL, BlockType::CARDS_SLIDER, BlockType::BANNERS_GRID, BlockType::BANNERS_GRID_K
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
                        BlockType::BANNERS_GRID_K,
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
                            BlockType::SEVERAL_COLS, BlockType::NIGHT_LENSES_SELECTION
                        ])
                    ),
                Forms\Components\Toggle::make('payload.is_slider')
                    ->label('Слайдер')
                    ->default(0)
                    ->hidden(
                        fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                            BlockType::NIGHT_LENSES_SELECTION
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
                    Forms\Components\Repeater::make('payload.tasks')
                        ->label('Пункты')
                        ->schema([
                            Forms\Components\TextInput::make('text')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->minItems(1)
                        ->required(),

                    Forms\Components\Textarea::make('payload.note_text')
                        ->label('Текст нижнего блока')
                        ->columnSpanFull()
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::APPARATUS_TASKS
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Карточки')
                        ->schema([
                            Forms\Components\Textarea::make('text')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->minItems(1)
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => !in_array(BlockType::from($get('type')), [
                        BlockType::APPARATUS_DISEASES,
                        BlockType::APPARATUS_CONTRAINDICATIONS,
                    ])
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Методы диагностики')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\TextInput::make('link')
                                ->label('Ссылка')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->extraAttributes(['class' => 'hidden']),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('media_collection'))
                                ->label('Мини-изображение')
                                ->imageEditor()
                                ->responsiveImages(),
                        ])
                        ->minItems(1)
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::DIAGNOSTIC_METHODS
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Методы лечения')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->extraAttributes(['class' => 'hidden'])
                                ->required(),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('media_collection'))
                                ->label('Мини-изображение')
                                ->imageEditor()
                                ->responsiveImages()
                                ->required(),
                        ])
                        ->minItems(1)
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::TREATMENT_METHODS
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Этапы')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Текст')
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->minItems(1)
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::RECEPTION_STEPS
                ),

                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('payload.items')
                        ->label('Методики')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\RichEditor::make('body_html')
                                ->label('Контент')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\TextInput::make('media_collection')
                                ->columnSpan('full')
                                ->hiddenLabel()
                                ->default(
                                    fn(Forms\Get $get) => $get('media_collection') ?? Str::uuid()->toString()
                                )
                                ->reactive()
                                ->required()
                                ->extraAttributes(['class' => 'hidden']),

                            SpatieMediaLibraryFileUpload::make('image')
                                ->collection(fn(Forms\Get $get) => $get('media_collection'))
                                ->label('Изображение')
                                ->imageEditor()
                                ->responsiveImages()
                                ->required(),
                        ])
                        ->minItems(1)
                        ->required(),
                ])->hidden(
                    fn(Forms\Get $get) => BlockType::from($get('type')) !== BlockType::APPARATUS_METHODS
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
            ->bulkActions([
                Tables\Actions\BulkAction::make('replaceCallToActionWithSpecialistBanner')
                    ->label('Заменить CTA на новый баннер')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('primary')
                    ->visible(fn(): bool => auth()->user()->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Заменить старые формы на новый баннер')
                    ->modalDescription('У выбранных блоков типа "Форма заявки" будет изменён тип на "Запись или обратный звонок". Payload старой формы будет очищен, чтобы не тащить неиспользуемые данные в новый статичный баннер.')
                    ->action(function (Collection $records): void {
                        $records
                            ->where('type', BlockType::CALL_TO_ACTION)
                            ->each(function (Block $block): void {
                                $block->forceFill([
                                    'type' => BlockType::BANNER_SPECIALIST_CALLBACK,
                                    'payload' => [],
                                ])->save();
                            });
                    }),
                Tables\Actions\BulkAction::make('excludeDoctorsAltDoctors')
                    ->label('Исключить врачей')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('city_ids')
                            ->label('Фильтр по городам')
                            ->options(\App\Models\City::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->live(),
                        Forms\Components\Select::make('doctor_ids')
                            ->label('Специалисты для исключения')
                            ->multiple()
                            ->required()
                            ->options(fn(Forms\Get $get): array => static::getDoctorOptionsQuery($get('city_ids'))
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                                ->all())
                            ->searchable()
                            ->getSearchResultsUsing(fn(string $search, Forms\Get $get): array => static::getDoctorOptionsQuery($get('city_ids'), $search)
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim($doctor->surname . ' ' . $doctor->name)])
                                ->all())
                            ->getOptionLabelsUsing(fn(array $values): array => static::getDoctorLabels($values))
                            ->helperText('Выбранные врачи будут добавлены в список исключений у всех отмеченных блоков типа "Специалисты (альтернативный)".'),
                    ])
                    ->modalHeading('Исключить врачей у выбранных блоков')
                    ->modalDescription('Добавляет выбранных врачей в `payload.excluded_doctors` без удаления уже заданных исключений.')
                    ->action(function (Collection $records, array $data): void {
                        $selectedDoctorIds = collect($data['doctor_ids'] ?? [])
                            ->filter()
                            ->values();

                        if ($selectedDoctorIds->isEmpty()) {
                            return;
                        }

                        $records
                            ->where('type', BlockType::DOCTORS_ALT)
                            ->each(function (Block $block) use ($selectedDoctorIds): void {
                                $payload = $block->payload ?? [];
                                $excludedDoctorIds = collect($payload['excluded_doctors'] ?? [])
                                    ->merge($selectedDoctorIds)
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all();

                                $payload['excluded_doctors'] = $excludedDoctorIds;

                                $block->forceFill([
                                    'payload' => $payload,
                                ])->save();
                            });
                    }),
                Tables\Actions\BulkAction::make('clearDoctorsAltSelection')
                    ->label('Очистить выбранных врачей')
                    ->icon('heroicon-o-users')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Очистить список врачей')
                    ->modalDescription('У выбранных блоков типа "Специалисты (альтернативный)" будет очищен старый список `payload.doctors`. После этого блок начнет работать по новой схеме: все врачи текущего города минус исключенные.')
                    ->action(function (Collection $records): void {
                        $records
                            ->where('type', BlockType::DOCTORS_ALT)
                            ->each(function (Block $block): void {
                                $payload = $block->payload ?? [];
                                unset($payload['doctors']);

                                $block->forceFill([
                                    'payload' => $payload,
                                ])->save();
                            });
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
