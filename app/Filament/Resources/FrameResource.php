<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\FrameGender;
use App\Filament\Forms\Components\CuratorUrlPicker;
use App\Filament\Resources\FrameResource\Pages;
use App\Models\Frame;
use App\Models\FrameColor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FrameResource extends Resource
{
    protected static ?string $model = Frame::class;

    protected static ?string $navigationGroup = 'Каталог оправ';

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'Оправы';

    protected static ?string $modelLabel = 'оправа';

    protected static ?string $pluralModelLabel = 'оправы';

    protected static ?string $recordTitleAttribute = 'model';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()?->hasRole('demo') === true)
            ->schema([
                Forms\Components\Section::make('Основные данные')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->activeOrdered(),
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Без бренда'),

                        Forms\Components\TextInput::make('model')
                            ->label('Модель')
                            ->maxLength(160)
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Краткое описание')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        CuratorUrlPicker::make('curator_media_id')
                            ->label('Фотография оправы')
                            ->buttonLabel('Выбрать фотографию')
                            ->directory('kids-optics/frames')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Рекомендуемый размер — не менее 800 × 626 px.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\Select::make('colors')
                            ->label('Цвета')
                            ->relationship(
                                name: 'colors',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->activeOrdered(),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (FrameColor $record): string => view(
                                    'filament.forms.components.frame-color-option',
                                    ['color' => $record],
                                )->render(),
                            )
                            ->allowHtml()
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('ageGroups')
                            ->label('Возрастные категории')
                            ->relationship(
                                name: 'ageGroups',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->activeOrdered(),
                            )
                            ->multiple()
                            ->preload()
                            ->required()
                            ->minItems(1),

                        Forms\Components\Select::make('genders')
                            ->label('Для кого')
                            ->options(FrameGender::options())
                            ->multiple()
                            ->required()
                            ->minItems(1)
                            ->maxItems(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Публикация')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_hit')
                            ->label('Хит')
                            ->default(false),

                        Forms\Components\Toggle::make('is_school_choice')
                            ->label('Отличный выбор для школы')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Показывать на сайте')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ageGroups.name')
                    ->label('Возраст')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_hit')
                    ->label('Хит')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_school_choice')
                    ->label('Для школы')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrames::route('/'),
            'create' => Pages\CreateFrame::route('/create'),
            'edit' => Pages\EditFrame::route('/{record}/edit'),
        ];
    }
}
