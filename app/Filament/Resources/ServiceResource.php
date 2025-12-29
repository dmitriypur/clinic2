<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\City;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $label = 'Услуга';

    protected static ?string $pluralLabel = 'Услуги';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название (заголовок)')
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\TextInput::make('uuid')
                    ->label('Внешний идентификатор')
                    ->placeholder('н-р: 215e537e-f097-11ed-b52e-fc3cbccb3d9b')
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\Select::make('parent_id')
                    ->label('Родительская категория')
                    ->relationship('parent', 'title', function (Builder $query, ?Service $record) {
                        if ($record) {
                            return $query->where('id', '!=', $record->id);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0)
                    ->columnSpanFull(),

                Forms\Components\Select::make('cities')
                    ->label('Доступность в городах')
                    ->relationship('cities', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Если пусто - услуга доступна везде'),

                Forms\Components\SpatieMediaLibraryFileUpload::make('default')
                    ->label('Изображение')
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название (заголовок)')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('prices_count')
                    ->label('Кол-во цен')
                    ->counts('prices')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('parent_id');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
