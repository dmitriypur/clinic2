<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ElementResource\Pages;
use App\Models\Element;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ElementResource extends Resource
{
    protected static ?string $model = Element::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle';

    protected static ?string $navigationLabel = 'Элементы';

    protected static ?string $label = 'Элемент';

    protected static ?string $pluralLabel = 'Элементы';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;


    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название (заголовок)')
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\TextInput::make('subtitle')
                    ->label('Подзаголовок')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('description_html')
                    ->label('Описание')
                    ->columnSpanFull(),

                Forms\Components\Checkbox::make('has_price')
                    ->label('Есть прайс-лист')
                    ->reactive()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('uuid')
                    ->label('Внешний идентификатор')
                    ->placeholder('н-р: 215e537e-f097-11ed-b52e-fc3cbccb3d9b')
                    ->columnSpanFull()
                    ->hidden(fn(Forms\Get $get) => !$get('has_price'))
                    ->required(fn(Forms\Get $get) => !!$get('has_price')),

                Forms\Components\Checkbox::make('has_an_appointment')
                    ->label('Есть запись на приём')
                    ->reactive()
                    ->columnSpanFull(),

                Forms\Components\SpatieMediaLibraryFileUpload::make('default')
                    ->label('Изображение')
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\Select::make('block_id')
                    ->label('Блок')
                    ->relationship('block', 'title'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название (заголовок)'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListElements::route('/'),
            'create' => Pages\CreateElement::route('/create'),
            'edit' => Pages\EditElement::route('/{record}/edit'),
        ];
    }
}
