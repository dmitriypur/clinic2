<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ConfiguresFrameDictionaryResource;
use App\Filament\Resources\FrameColorResource\Pages;
use App\Models\FrameColor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FrameColorResource extends Resource
{
    use ConfiguresFrameDictionaryResource;

    protected static ?string $model = FrameColor::class;

    protected static ?string $navigationGroup = 'Каталог оправ';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Цвета';

    protected static ?string $modelLabel = 'цвет';

    protected static ?string $pluralModelLabel = 'цвета';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->required(),
            Forms\Components\ColorPicker::make('hex')
                ->label('Цвет')
                ->regex('/^#[0-9A-Fa-f]{6}$/')
                ->unique(ignoreRecord: true)
                ->required(),
            ...self::dictionaryStateFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return self::dictionaryTable($table, [
            Tables\Columns\ColorColumn::make('hex')
                ->label('Цвет'),
            Tables\Columns\TextColumn::make('name')
                ->label('Название')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('frames_count')
                ->label('Оправ')
                ->counts('frames'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrameColors::route('/'),
            'create' => Pages\CreateFrameColor::route('/create'),
            'edit' => Pages\EditFrameColor::route('/{record}/edit'),
        ];
    }
}
