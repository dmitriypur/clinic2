<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use App\Models\City;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Дочерняя услуга';

    protected static ?string $pluralModelLabel = 'Дочерние услуги';

    protected static ?string $title = 'Дочерние услуги';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название (заголовок)')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('uuid')
                    ->label('Внешний идентификатор')
                    ->placeholder('н-р: 215e537e-f097-11ed-b52e-fc3cbccb3d9b')
                    ->required(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),

                Forms\Components\Select::make('cities')
                    ->label('Доступность в городах')
                    ->relationship('cities', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('Если пусто - услуга доступна везде'),

                Forms\Components\SpatieMediaLibraryFileUpload::make('default')
                    ->label('Изображение'),

                Repeater::make('prices')
                    ->label('Цены')
                    ->relationship('prices')
                    ->schema([
                        Select::make('city_id')
                            ->label('Город')
                            ->options(City::pluck('name', 'id'))
                            ->nullable()
                            ->placeholder('По умолчанию (Все города)')
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        
                        TextInput::make('price')
                    ->label('Цена')
                    ->numeric()
                    ->required(),

                TextInput::make('old_price')
                    ->label('Старая цена')
                    ->numeric()
                    ->nullable(),
                
                Toggle::make('price_from')
                            ->label('Цена от')
                            ->inline(false),
                    ])
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->reorderableWithButtons(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('prices_count')
                    ->label('Кол-во цен')
                    ->counts('prices')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
