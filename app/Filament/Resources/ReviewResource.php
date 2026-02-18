<?php

namespace App\Filament\Resources;

use App\Enums\PageType;
use App\Enums\ResourcesForReviews;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Отзывы';

    protected static ?string $label = 'Отзыв';

    protected static ?string $pluralLabel = 'Отзывы';

    protected static ?string $navigationIcon = 'heroicon-s-cube';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\Select::make('page_id')
                        ->relationship('pages', 'title')
                        ->multiple()
                        ->columnSpanFull()
                        ->label('Услуга')
                        ->options(Page::query()->where('type', '=', PageType::Services)->where('active', '=', 1)->pluck('title', 'id')),

                    Forms\Components\Select::make('cities')
                        ->relationship('cities', 'name')
                        ->multiple()
                        ->preload()
                        ->columnSpanFull()
                        ->label('Города')
                        ->helperText('Если пусто - отзыв доступен во всех городах'),

                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'surname')
                        ->searchable()
                        ->preload()
                        ->columnSpanFull()
                        ->label('Специалист (необязательно)')
                        ->options(Doctor::query()->pluck('surname', 'id')),

                    Forms\Components\TextInput::make('name')
                        ->label('Имя')
                        ->required(),

                    Forms\Components\RichEditor::make('body_html')
                        ->label('Отзыв')
                        ->required(),

                    Forms\Components\Checkbox::make('is_home')
                        ->label('На главной странице')
                        ->default(false),

                    Forms\Components\Select::make('rating')
                        ->label('Рейтинг')
                        ->options([
                            1 => '1 звезда',
                            2 => '2 звезды',
                            3 => '3 звезды',
                            4 => '4 звезды',
                            5 => '5 звезд',
                        ])
                        ->required(),
                    Forms\Components\Select::make('resource')
                        ->label('Ресурс отзыва')
                        ->options(ResourcesForReviews::toArray()),

                    Forms\Components\TextInput::make('link_resource')
                        ->label('Ссылка на ресурс'),

                    DatePicker::make('get_date')
                        ->label('Дата')
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Имя'),
                Tables\Columns\TextColumn::make('doctor.surname')->label('Доктор'),
                Tables\Columns\TextColumn::make('cities.name')->label('Города'),
                Tables\Columns\TextColumn::make('pages.title')->label('Услуги'),
                SelectColumn::make('resource')
                    ->options(ResourcesForReviews::toArray()
                    ),
               ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('detach_service')
                        ->label('Отвязать от услуги')
                        ->form([
                            Forms\Components\Select::make('page_id')
                                ->label('Услуга')
                                ->options(Page::query()->where('type', '=', PageType::Services)->where('active', '=', 1)->pluck('title', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn(Review $review) => $review->pages()->detach($data['page_id']));
                            self::clearReviewCaches();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-link-slash'),
                    Tables\Actions\BulkAction::make('assign_cities')
                        ->label('Назначить города')
                        ->form([
                            Forms\Components\Select::make('city_ids')
                                ->label('Города')
                                ->options(City::query()->where('active', true)->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('mode')
                                ->label('Режим')
                                ->options([
                                    'add' => 'Добавить к текущим',
                                    'replace' => 'Заменить текущие',
                                ])
                                ->default('add')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $cityIds = $data['city_ids'] ?? [];
                            $mode = $data['mode'] ?? 'add';

                            $records->each(function (Review $review) use ($cityIds, $mode): void {
                                if ($mode === 'replace') {
                                    $review->cities()->sync($cityIds);
                                    return;
                                }

                                $review->cities()->syncWithoutDetaching($cityIds);
                            });

                            self::clearReviewCaches();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-map-pin'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    private static function clearReviewCaches(): void
    {
        Cache::forget('reviews');
        Cache::forget('reviews_with_doctor_cities');
        Cache::forget('reviews_with_cities_global');

        foreach (City::query()->where('active', true)->pluck('slug') as $slug) {
            Cache::forget("reviews_with_cities_{$slug}");
        }
    }
}
