<?php

namespace App\Filament\Actions\Tables;

use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ReplicateAction;
use Illuminate\Database\Eloquent\Model;

class ReplicateBlockAction extends ReplicateAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->excludeAttributes(['order_column']);

        $this->form([
            Select::make('cities')
                ->label('Города')
                ->multiple()
                ->relationship('cities', 'name')
                ->preload()
                ->default(fn (Model $record) => $record->cities->pluck('id')->toArray())
                ->helperText('Выберите города для копии блока. По умолчанию выбраны те же, что и у оригинала.')
        ]);

        $this->after(function (Model $replica, array $data) {
            // Sync cities
            if (isset($data['cities'])) {
                $replica->cities()->sync($data['cities']);
            }
            
            // ReplicateAction handles simple attribute copying automatically before this hook
            // But we need to manually handle media as it's a separate relation/library
        });

        // We need to use beforeReplicaSaved or just rely on the record instance in after() 
        // essentially we need access to the original record to copy media
        // ReplicateAction::after() signature is (Model $replica, Model $record, array $data)
        
        // Let's redefine after to be clean
        $this->after(function (Model $replica, Model $record, array $data) {
             // Sync cities from form data
            if (isset($data['cities'])) {
                $replica->cities()->sync($data['cities']);
            } else {
                 // Fallback if form field was missing for some reason, though with default it should be there
                 $replica->cities()->sync($record->cities->pluck('id')->toArray());
            }

            // Copy media
            if (method_exists($record, 'media') || $record->media) {
                foreach ($record->media as $media) {
                    $media->copy($replica, $media->collection_name, $media->disk);
                }
            }
        });
    }
}
