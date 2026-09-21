<?php

declare(strict_types=1);

namespace App\Filament\Resources\Concerns;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;

trait ConfiguresFrameDictionaryResource
{
    /** @return array<Forms\Components\Component> */
    protected static function dictionaryStateFields(): array
    {
        return [
            Forms\Components\TextInput::make('sort_order')
                ->label('Порядок сортировки')
                ->numeric()
                ->default(0)
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('Активно')
                ->default(true)
                ->required(),
        ];
    }

    /** @param array<Tables\Columns\Column> $columns */
    protected static function dictionaryTable(Table $table, array $columns): Table
    {
        return $table
            ->columns([
                ...$columns,
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
