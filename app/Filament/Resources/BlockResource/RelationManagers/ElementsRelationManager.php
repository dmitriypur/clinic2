<?php

namespace App\Filament\Resources\BlockResource\RelationManagers;

use App\Filament\Resources\ElementResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'elements';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Элемент';
    protected static ?string $pluralLabel = 'Элементы';

    public function form(Form $form): Form
    {
        return app(ElementResource::class)->form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
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
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('order_column');
    }
}
