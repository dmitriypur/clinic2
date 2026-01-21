<?php

namespace App\Filament\Resources\PageServiceResource\RelationManagers;

use App\Enums\BlockType;
use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
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
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, $data): Model {
                        if ($data['type'] === BlockType::AUTHOR->value) {
                            Cache::forget('author');
                        }
                        $record->update($data);
                        return $record;
                    }),
                ReplicateAction::make()
                    ->label('Клонировать')
                    ->beforeReplicaSaved(function (Block $record, Block $replica): void {
                        $nextOrder = ($this->getOwnerRecord()->blocks()->max('order_column') ?? 0) + 1;
                        $replica->order_column = $nextOrder;

                        $record->media->each(function ($image) use ($replica) {
                            $replica
                                ->addMediaFromStream($image->stream())
                                ->usingFileName($image->file_name)
                                ->toMediaCollection($image->collection_name);
                        });
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('order_column');
    }
}
