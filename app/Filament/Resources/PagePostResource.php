<?php

namespace App\Filament\Resources;

use App\Enums\BlockType;
use App\Enums\PageType;
use App\Enums\ResourcesForReviews;
use App\Filament\Resources\PageServiceResource\Pages;
use App\Filament\Resources\PageServiceResource\RelationManagers;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PagePostResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Блог';

    protected static ?string $navigationLabel = 'Записи';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'posts';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', '=', PageType::Posts);
    }

    public static function form(Form $form): Form
    {

        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Заголовок')
                        ->required(),

                    Forms\Components\TextInput::make('breadcrumbs_title')
                        ->label('Заголовок для хлебных крошек')
                        ->helperText('По-умолчанию берется заголовок'),

                    SpatieMediaLibraryFileUpload::make('default')
                        ->label('Изображение')
                        ->imageEditor()
                        ->responsiveImages()
                        ->openable(),

                    Forms\Components\RichEditor::make('body_html')
                        ->label('Текст страницы'),

                    Forms\Components\Checkbox::make('is_price_page')
                        ->label('Страница с прайс-листом')
                        ->columnSpanFull(),

                    Forms\Components\Checkbox::make('active')
                        ->label('Опубликована'),

                    Forms\Components\Select::make('category_id')
                        ->label('Категория')
                        ->relationship('category', 'title')
                        ->required(),

                    Forms\Components\Select::make('tag_id')
                        ->relationship('tags', 'title')
                        ->options(Tag::query()->pluck('title', 'id'))
                        ->multiple(),

                    Forms\Components\Select::make('type')
                        ->required()
                        ->default(PageType::Posts->value)
                        ->label('Шаблон страницы')
                        ->options(collect(PageType::toArray())->filter(function ($item, $id) {
                            return $id == PageType::Posts->value;
                        }))
                        ->reactive(),

                    Forms\Components\Textarea::make('header_scripts')
                        ->label('Скрипты в head'),

                    Forms\Components\TextInput::make('sorting')
                        ->label('Порядок страниц (сортировка)'),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => PagePostResource\Pages\ListPagePosts::route('/'),
            'create' => PagePostResource\Pages\CreatePagePost::route('/create'),
            'edit' => PagePostResource\Pages\EditPagePost::route('/{record}/edit'),
        ];
    }
}
