<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleImportResource\Pages;
use App\Models\ArticleImport;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleImportResource extends Resource
{
    protected static ?string $model = ArticleImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Блог';

    protected static ?string $navigationLabel = 'Импорты статей';

    protected static ?string $slug = 'article-imports';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn(string $state) => ArticleImport::statuses()[$state] ?? $state)
                    ->badge()
                    ->color(fn(string $state) => ArticleImport::statusColors()[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('document_url')
                    ->label('Документ')
                    ->limit(60)
                    ->url(fn(ArticleImport $record) => $record->document_url)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('page.title')
                    ->label('Статья')
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Кто запустил')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Завершено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Ошибка')
                    ->limit(80)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('warnings')
                    ->label('Предупреждения')
                    ->formatStateUsing(fn(?array $state) => $state ? implode(' | ', $state) : '')
                    ->limit(80)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ArticleImport::statuses()),
            ])
            ->actions([
                Tables\Actions\Action::make('openPage')
                    ->label('Открыть статью')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(ArticleImport $record) => $record->page
                        ? PagePostResource::getUrl('edit', ['record' => $record->page])
                        : null)
                    ->visible(fn(ArticleImport $record) => $record->page_id !== null),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'page']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleImports::route('/'),
        ];
    }
}
