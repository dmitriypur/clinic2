<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Города';

    protected static ?string $modelLabel = 'Город';

    protected static ?string $pluralModelLabel = 'Города';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основное')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'regex:/^[a-z0-9-]+$/',
                                Rule::notIn([
                                    'api', 'admin', 'profile', 'search', 'live-search',
                                    'doctors', 'stati', 'directory', 'tags', 'reviews',
                                    'sitemap.xml', 'sitemap.html', 'robots.txt',
                                    'yml-feed', 'call-request', 'clear-price', 'form',
                                    'login', 'logout'
                                ]),
                            ])
                            ->helperText('Только латинские буквы, цифры и дефис'),

                        Toggle::make('is_default')
                            ->label('Основной город')
                            ->helperText('Открывается без префикса в URL'),

                        Toggle::make('active')
                            ->label('Активен')
                            ->default(true),
                    ])->columns(2),

                Section::make('Контакты города')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('postal_code')
                            ->label('Индекс'),
                        TextInput::make('address')
                            ->label('Адрес'),
                        TextInput::make('metro')
                            ->label('Метро'),
                        TextInput::make('coordinates')
                            ->label('Координаты (lat, lng)'),
                        TextInput::make('schedule')
                            ->label('Режим работы')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Филиалы')
                    ->schema([
                        Repeater::make('branches')
                            ->label('Филиалы города')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название филиала')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('Телефон'),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                TextInput::make('postal_code')
                                    ->label('Индекс'),
                                TextInput::make('address')
                                    ->label('Адрес'),
                                TextInput::make('metro')
                                    ->label('Метро'),
                                TextInput::make('coordinates')
                                    ->label('Координаты (lat, lng)'),
                                TextInput::make('schedule')
                                    ->label('Режим работы'),
                                TextInput::make('price')
                                    ->label('Акционная цена'),
                                TextInput::make('external_id')
                                    ->label('External ID'),
                            ])
                            ->columns(2)
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),

                Section::make('Реквизиты')
                    ->schema([
                        Repeater::make('details')
                            ->label('Реквизиты города')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название организации'),
                                TextInput::make('fullname')
                                    ->label('Полное наименование организации'),
                                TextInput::make('director')
                                    ->label('Директор'),
                                TextInput::make('legal_address')
                                    ->label('Юридический адрес'),
                                TextInput::make('postal_address')
                                    ->label('Почтовый адрес'),
                                TextInput::make('ogrn')
                                    ->label('ОГРН'),
                                TextInput::make('inn')
                                    ->label('ИНН/КПП'),
                            ])
                            ->columns(2)
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),

                Section::make('Соцсети')
                    ->schema([
                        TextInput::make('social_links.vk')->label('VK'),
                        TextInput::make('social_links.telegram')->label('Telegram'),
                        TextInput::make('social_links.youtube')->label('YouTube'),
                        TextInput::make('social_links.rutube')->label('RuTube'),
                        TextInput::make('social_links.vk_video')->label('VK Видео'),
                    ])->columns(2),

                Section::make('Режим работы в праздники')
                    ->schema([
                        Toggle::make('show_special_schedule')->label('Показывать'),
                        TextInput::make('special_schedule_title')->label('Заголовок'),
                        Forms\Components\Textarea::make('special_schedule')->label('Текст'),
                    ])->columns(2),

                Section::make('SEO склонения')
                    ->schema([
                        TextInput::make('seo_cases.prepositional')
                            ->label('Предложный падеж (где?)')
                            ->placeholder('в Москве')
                            ->helperText('Используется в заголовках: "Лечение в Москве"'),

                        TextInput::make('seo_cases.genitive')
                            ->label('Родительный падеж (кого/чего?)')
                            ->placeholder('Москвы')
                            ->helperText('Используется в описаниях: "Клиника Москвы"'),
                        TextInput::make('seo_cases.accusative')
                            ->label('Винительный падеж (кого/что?)')
                            ->placeholder('Москву')
                            ->helperText('Используется в описаниях: "Ехать в Москву"'),
                    ])->columns(2),

                Section::make('Аналитика и Скрипты')
                    ->schema([
                        Forms\Components\Repeater::make('header_scripts')
                            ->label('Скрипты в <head>')
                            ->schema([
                                TextInput::make('name')->label('Название (для админки)')->required(),
                                Forms\Components\Textarea::make('value')->label('Код скрипта')->required(),
                            ]),
                        Forms\Components\Repeater::make('body_scripts')
                            ->label('Скрипты перед </body>')
                            ->schema([
                                TextInput::make('name')->label('Название (для админки)')->required(),
                                Forms\Components\Textarea::make('value')->label('Код скрипта')->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Основной')
                    ->boolean(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
