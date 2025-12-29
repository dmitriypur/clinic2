<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationGroup = 'Блог';

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'categories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->required(),

                    Forms\Components\RichEditor::make('body_html')
                        ->label('Описание категории'),

//                    Forms\Components\Select::make('parent_id')
//                        ->relationship(name: 'parent', titleAttribute: 'title', ignoreRecord: true)
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
//                        ->afterStateHydrated(fn(Forms\Components\Checkbox $component) => $component->state(true))
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
                Tables\Columns\TextColumn::make('title')->label('Заголовок'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
