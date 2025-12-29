<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Filament\Resources\PromotionResource\RelationManagers;
use App\Models\Page;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Акции';

    protected static ?string $label = 'Акция';

    protected static ?string $pluralLabel = 'Акции';

    protected const PROMOTIONS_CACHE_KEY = 'active_promotions';

    public static function forgetPromotionsCache(): void
    {
        Cache::forget(static::PROMOTIONS_CACHE_KEY);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название')
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\Select::make('cities')
                    ->label('Акция для городов')
                    ->relationship('cities', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Если пусто - акция действует везде'),

                Forms\Components\TextInput::make('description_html')
                    ->label('Ссылка')
                    ->columnSpanFull(),

                Forms\Components\SpatieMediaLibraryFileUpload::make('default')
                    ->label('Изображение для блока')
                    ->openable()
                    ->columnSpanFull()
                    ->required()
                    ->afterStateUpdated(fn () => static::forgetPromotionsCache()),

                Forms\Components\SpatieMediaLibraryFileUpload::make('block_mobile')
                    ->label('Изображение для блока (мобильное)')
                    ->collection('block_mobile')
                    ->openable()
                    ->columnSpanFull()
                    ->required()
                    ->afterStateUpdated(fn () => static::forgetPromotionsCache()),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Опубликовано'),

                Forms\Components\Checkbox::make('archived')
                    ->label('Архивировать')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn () => static::forgetPromotionsCache()),
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
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
