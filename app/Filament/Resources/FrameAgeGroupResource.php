<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ConfiguresFrameDictionaryResource;
use App\Filament\Resources\FrameAgeGroupResource\Pages;
use App\Models\FrameAgeGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FrameAgeGroupResource extends Resource
{
    use ConfiguresFrameDictionaryResource;

    protected static ?string $model = FrameAgeGroup::class;

    protected static ?string $navigationGroup = 'Каталог оправ';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Возрастные категории';

    protected static ?string $modelLabel = 'возрастная категория';

    protected static ?string $pluralModelLabel = 'возрастные категории';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->required(),
            Forms\Components\TextInput::make('code')
                ->label('Код')
                ->helperText('Стабильный технический код, например 7-12.')
                ->alphaDash()
                ->maxLength(50)
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
            Tables\Columns\TextColumn::make('code')
                ->label('Код')
                ->searchable(),
            Tables\Columns\TextColumn::make('frames_count')
                ->label('Оправ')
                ->counts('frames'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrameAgeGroups::route('/'),
            'create' => Pages\CreateFrameAgeGroup::route('/create'),
            'edit' => Pages\EditFrameAgeGroup::route('/{record}/edit'),
        ];
    }
}
