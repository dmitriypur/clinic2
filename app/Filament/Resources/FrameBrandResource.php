<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ConfiguresFrameDictionaryResource;
use App\Filament\Resources\FrameBrandResource\Pages;
use App\Models\FrameBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FrameBrandResource extends Resource
{
    use ConfiguresFrameDictionaryResource;

    protected static ?string $model = FrameBrand::class;

    protected static ?string $navigationGroup = 'Каталог оправ';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Бренды';

    protected static ?string $modelLabel = 'бренд';

    protected static ?string $pluralModelLabel = 'бренды';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->required(),
            ...self::dictionaryStateFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return self::dictionaryTable($table, [
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
            'index' => Pages\ListFrameBrands::route('/'),
            'create' => Pages\CreateFrameBrand::route('/create'),
            'edit' => Pages\EditFrameBrand::route('/{record}/edit'),
        ];
    }
}
