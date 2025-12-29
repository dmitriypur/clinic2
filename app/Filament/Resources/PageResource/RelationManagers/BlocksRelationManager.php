<?php

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Filament\Actions\Tables\ReplicateBlockAction;
use App\Filament\Resources\BlockResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class BlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Блок';

    protected static ?string $pluralModelLabel = 'Блоки';

    protected static ?string $title = 'Блоки';

    public function form(Form $form): Form
    {
        return app(BlockResource::class)->form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ReplicateBlockAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('order_column');
    }
}
