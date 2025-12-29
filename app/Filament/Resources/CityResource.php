<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
                            ->unique(ignoreRecord: true),
                            
                        Toggle::make('is_default')
                            ->label('Основной город')
                            ->helperText('Открывается без префикса в URL'),
                            
                        Toggle::make('active')
                            ->label('Активен')
                            ->default(true),
                    ])->columns(2),
                    
                Section::make('Контакты')
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
                            ->label('Адрес')
                            ->columnSpanFull(),
                        TextInput::make('metro')
                            ->label('Метро'),
                        TextInput::make('coordinates')
                            ->label('Координаты (lat, lng)'),
                        TextInput::make('schedule')
                            ->label('Режим работы')
                            ->columnSpanFull(),
                    ])->columns(2),

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
                            ->placeholder('в Санкт-Петербурге')
                            ->helperText('Используется в заголовках: "Лечение ... в Санкт-Петербурге"'),
                            
                        TextInput::make('seo_cases.genitive')
                            ->label('Родительный падеж (кого/чего?)')
                            ->placeholder('Санкт-Петербурга')
                            ->helperText('Используется в описаниях: "Клиника ... Санкт-Петербурга"'),
                    ])->columns(2),

                Section::make('UTM Телефоны')
                    ->schema([
                        Forms\Components\Repeater::make('utm_phones')
                            ->label('Правила подмены')
                            ->schema([
                                Forms\Components\TextInput::make('source')
                                    ->label('utm_source')
                                    ->columnSpan('full')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Телефон по умолчанию для этого source')
                                    ->columnSpan('full'),
                                Forms\Components\Repeater::make('medium')
                                    ->label('Уточнения для utm_medium')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('utm_medium')
                                            ->columnSpan('full')
                                            ->required(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Телефон')
                                            ->columnSpan('full')
                                            ->required(),
                                    ])
                            ])
                    ]),

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
