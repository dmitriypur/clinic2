<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Filament\Resources\DoctorResource\RelationManagers;
use App\Models\Doctor;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationLabel = 'Специалисты';

    protected static ?string $label = 'Специалист';

    protected static ?string $pluralLabel = 'Специалисты';

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\Section::make()
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->uuid()
                            ->label('Идентификатор в 1С')
                            ->required(),

                        Forms\Components\Select::make('cities')
                            ->label('Города приёма')
                            ->relationship('cities', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Если пусто - врач доступен везде'),

                        Forms\Components\TextInput::make('surname')
                            ->label('Фамилия')
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Имя и отчество')
                            ->required(),

                        Forms\Components\TextInput::make('speciality')
                            ->label('Специальность')
                            ->required(),

                        Forms\Components\TextInput::make('job_title')
                            ->label('Должность')
                            ->required(),

                        Forms\Components\Textarea::make('excerpt')
                            ->label('Краткая информация')
                            ->columnSpan('full')
                            ->required(),

                        Forms\Components\RichEditor::make('bio')
                            ->label('Информация')
                            ->columnSpan('full')
                            ->required(),
                    ]),

                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('video_url')->label('Ссылка на видео'),
                ]),

                Forms\Components\Section::make('Дополнительная информация')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('extra.seniority')->label('Стаж'),
                        Forms\Components\TextInput::make('extra.category')->label('Категория'),
                        Forms\Components\TextInput::make('extra.receives')->label('Ведёт приём')->columnSpanFull(),

                        Forms\Components\Repeater::make('extra.education')
                            ->columnSpanFull()
                            ->label('Образование')
                            ->schema([
                                Forms\Components\TextInput::make('title')->label('Учебное заведение'),
                                Forms\Components\Repeater::make('educational_institution')
                                    ->label('Период обучения')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('year')->label('Год'),
                                        Forms\Components\TextInput::make('specialty')->label('Специальность'),
                                        Forms\Components\TextInput::make('level')->label('Уровень образования'),
                                    ])
                            ]),

                        Forms\Components\Repeater::make('extra.professional_development')
                            ->columnSpanFull()
                            ->label('Повышение квалификации')
                            ->columns()
                            ->schema([
                                Forms\Components\TextInput::make('year')->label('Год'),
                                Forms\Components\TextInput::make('title')->label('Название'),
                            ]),

                        Forms\Components\Repeater::make('extra.skills')
                            ->columnSpanFull()
                            ->label('Профессиональные навыки')
                            ->simple(
                                Forms\Components\TextInput::make('title')->label('Название'),
                            ),

                        Forms\Components\TextInput::make('extra.rating')
                            ->label('Текст рейтига'),
                    ]),


                Forms\Components\Section::make('Документы, подтверждающие квалификацию')->schema([
                    SpatieMediaLibraryFileUpload::make('documents')
                        ->collection('documents')
                        ->multiple()
                        ->label('Фото')
                        ->openable()
                        ->reorderable(),
                ]),

                Forms\Components\Section::make('Отзывы о сепциалисте')->schema([
                    Forms\Components\Repeater::make('extra.reviews')
                        ->columnSpanFull()
                        ->label('Сервис')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->hiddenLabel()
                                ->columnSpan('full')
                                ->default(
                                    fn(Forms\Get $get) => $get('uuid') ??
                                        Str::uuid()->toString()
                                )
                                ->reactive()
                                ->disabled()
                                ->dehydrated()
                                ->extraAttributes(['class' => 'hidden']),
                            Forms\Components\TextInput::make('url')->label('Ссылка'),
                            SpatieMediaLibraryFileUpload::make('review_icon')
                                ->collection(fn(Forms\Get $get) => $get('uuid'))
                                ->label('Иконка')
                                ->openable(),
                        ]),
                ]),

                Forms\Components\Section::make()->schema([
                    SpatieMediaLibraryFileUpload::make('default')
                        ->label('Фото')
                        ->openable(),
                ]),

                Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('seo.title'),

                    Forms\Components\TextInput::make('handle')
                        ->label('URL псевдоним')
                        ->prefix(config('app.url') . '/doctors/')
                        ->unique(ignorable: fn($record) => $record)
                        ->afterStateUpdated(function (Get $get, Set $set, $record) {
                            if ($record) {
                                $set('show_redirect', true);
                            }
                        })
                        ->reactive(),

                    Forms\Components\Checkbox::make('show_redirect')
                        ->default(false)
                        ->reactive()
                        ->hidden(),

                    Forms\Components\Checkbox::make('redirect')
                        ->label(function (Get $get, $record) {
                            if (!$record) {
                                return "Создать редирект";
                            }

                            return "Создать редирект {$record->handle} → {$get('handle')}";
                        })
//                        ->afterStateHydrated(fn(Forms\Components\Checkbox $component) => $component->state(true))
                        ->hidden(fn(Get $get) => !$get('show_redirect')),

                    Forms\Components\TextInput::make('seo.canonical')
                        ->label('Канонический URL')
                        ->prefix(config('app.url') . '/doctors/'),

                    Forms\Components\Textarea::make('seo.description')
                        ->helperText(function (?string $state): string {
                            return (string)Str::of(strlen($state))
                                ->append(' / ')
                                ->append(160 . ' ')
                                ->append('символов');
                        })
                        ->reactive(),

                    Forms\Components\Checkbox::make('seo.noindex')
                        ->label('Запретить поисковикам индексировать эту страницу'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Имя'),
            ])
            ->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctors::route('/'),
            'create' => Pages\CreateDoctor::route('/create'),
            'edit' => Pages\EditDoctor::route('/{record}/edit'),
        ];
    }
}
