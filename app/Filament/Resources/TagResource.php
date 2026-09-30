<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Filament\Resources\TagResource\RelationManagers;
use App\Models\Category;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Блог';

    protected static ?string $navigationLabel = 'Тэги';

    protected static ?string $slug = 'tags';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->required(),
                ]),

                Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('seo.title'),

                    Forms\Components\TextInput::make('handle')
                        ->label('URL псевдоним')
                        ->prefix(config('app.url') . '/')
                        ->unique(ignorable: fn($record) => $record)
                        ->afterStateUpdated(function (Get $get, Set $set, $record) {
                            if ($record) {
                                $set('show_redirect', true);
                            }
                        })
                        ->reactive(),

                    Forms\Components\Checkbox::make('show_redirect')
                        ->default(false)
                        ->reactive()
                        ->hidden(),

                    Forms\Components\Checkbox::make('redirect')
                        ->label(function (Get $get, $record) {
                            if (!$record) {
                                return "Создать редирект";
                            }

                            return "Создать редирект {$record->handle} → {$get('handle')}";
                        })
                        ->hidden(fn(Get $get) => !$get('show_redirect')),

                    Forms\Components\TextInput::make('seo.canonical')
                        ->label('Канонический URL')
                        ->prefix(config('app.url') . '/'),

                    Forms\Components\Textarea::make('seo.description')
                        ->helperText(function (?string $state): string {
                            return (string)Str::of(strlen($state))
                                ->append(' / ')
                                ->append(160 . ' ')
                                ->append('символов');
                        })
                        ->reactive(),

                    Forms\Components\Checkbox::make('seo.noindex')
                        ->label('Запретить поисковикам индексировать эту страницу'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Tag $record, Tables\Actions\DeleteAction $action): void {
                            if (! $record->pages()->exists()) {
                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title('Нельзя удалить тег')
                                ->body('Тег привязан к странице. Сначала удалите эту связь.')
                                ->send();

                            $action->halt();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records, Tables\Actions\DeleteBulkAction $action): void {
                            if (! $records->contains(fn (Tag $tag): bool => $tag->pages()->exists())) {
                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title('Нельзя удалить выбранные теги')
                                ->body('Среди выбранных тегов есть привязанные к страницам. Сначала удалите эти связи.')
                                ->send();

                            $action->halt();
                        }),
                ]),
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
