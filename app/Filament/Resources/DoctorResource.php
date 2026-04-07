<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Filament\Resources\DoctorResource\RelationManagers;
use App\Models\Doctor;
use App\Support\DoctorAge;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

                        Forms\Components\TextInput::make('page_sort_order')
                            ->label('Порядок на странице специалистов')
                            ->numeric()
                            ->helperText('Меньше число — выше на публичной странице специалистов. Если пусто, используется порядок по умолчанию.'),

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
                        Forms\Components\Grid::make(4)
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('age_min_value')
                                    ->label('Возраст от')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live(),
                                Forms\Components\Select::make('age_min_unit')
                                    ->label('Единица')
                                    ->options([
                                        'months' => 'Месяцы',
                                        'years' => 'Годы',
                                    ])
                                    ->default('years')
                                    ->live(),
                                Forms\Components\TextInput::make('age_max_value')
                                    ->label('Возраст до')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live(),
                                Forms\Components\Select::make('age_max_unit')
                                    ->label('Единица')
                                    ->options([
                                        'months' => 'Месяцы',
                                        'years' => 'Годы',
                                    ])
                                    ->default('years')
                                    ->live(),
                            ]),
                        Forms\Components\Textarea::make('extra.receives_text')
                            ->label('Шаблон текста')
                            ->rows(2)
                            ->helperText('Необязательно. Доступны плейсхолдеры {min} и {max}. Если поле пустое, текст будет собран автоматически.')
                            ->columnSpanFull()
                            ->live(),
                        Forms\Components\Placeholder::make('receives_preview')
                            ->label('Предпросмотр')
                            ->columnSpanFull()
                            ->content(function (Get $get): string {
                                $display = DoctorAge::buildDisplay(
                                    DoctorAge::convertInputToMonths($get('age_min_value'), $get('age_min_unit')),
                                    DoctorAge::convertInputToMonths($get('age_max_value'), $get('age_max_unit')),
                                    $get('extra.receives_text'),
                                );

                                return $display ?: '—';
                            }),

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

    public static function hydrateAgeFields(array $data): array
    {
        $extra = (array) ($data['extra'] ?? []);
        $min = DoctorAge::splitMonths(DoctorAge::minMonths($extra));
        $max = DoctorAge::splitMonths(DoctorAge::maxMonths($extra));

        $data['age_min_value'] = $min['value'];
        $data['age_min_unit'] = $min['unit'];
        $data['age_max_value'] = $max['value'];
        $data['age_max_unit'] = $max['unit'];
        $data['extra']['receives_text'] = DoctorAge::receivesText($extra);

        return $data;
    }

    public static function dehydrateAgeFields(array $data): array
    {
        $extra = (array) ($data['extra'] ?? []);

        $extra['age_min_months'] = DoctorAge::convertInputToMonths($data['age_min_value'] ?? null, $data['age_min_unit'] ?? 'years');
        $extra['age_max_months'] = DoctorAge::convertInputToMonths($data['age_max_value'] ?? null, $data['age_max_unit'] ?? 'years');

        if (
            $extra['age_min_months'] !== null &&
            $extra['age_max_months'] !== null &&
            $extra['age_max_months'] < $extra['age_min_months']
        ) {
            throw ValidationException::withMessages([
                'age_max_value' => 'Возраст "до" не может быть меньше возраста "от".',
            ]);
        }

        $receivesText = trim((string) ($extra['receives_text'] ?? ''));
        $extra['receives_text'] = $receivesText !== '' ? $receivesText : null;

        unset($extra['receives']);

        $data['extra'] = $extra;

        unset(
            $data['age_min_value'],
            $data['age_min_unit'],
            $data['age_max_value'],
            $data['age_max_unit'],
        );

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('cities'))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Имя'),
                Tables\Columns\TextColumn::make('cities_list')
                    ->label('Города')
                    ->getStateUsing(fn(Doctor $record): string => $record->cities->pluck('name')->implode(', '))
                    ->placeholder('Все'),
                Tables\Columns\TextInputColumn::make('page_sort_order')
                    ->label('Порядок страницы')
                    ->type('number')
                    ->step(1)
                    ->rules(['nullable', 'integer'])
                    ->extraInputAttributes(['class' => 'w-24'])
                    ->updateStateUsing(function (Doctor $record, $state): ?int {
                        $value = is_numeric($state) ? (int) $state : null;
                        $record->update(['page_sort_order' => $value]);

                        return $value;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->getStateUsing(fn(Doctor $record): bool => !((bool) data_get($record->seo ?? [], 'noindex', false))),
            ])
            ->filters([
                SelectFilter::make('cities')
                    ->label('Города')
                    ->relationship('cities', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function (Doctor $doctor): void {
                                $seo = is_array($doctor->seo) ? $doctor->seo : [];
                                $seo['noindex'] = false;
                                $doctor->update(['seo' => $seo]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Отключить')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function (Doctor $doctor): void {
                                $seo = is_array($doctor->seo) ? $doctor->seo : [];
                                $seo['noindex'] = true;
                                $doctor->update(['seo' => $seo]);
                            });
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
