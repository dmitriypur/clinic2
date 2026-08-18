<?php

namespace App\Providers\Filament;

use App\Filament\Resources\NavigationResource;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Page;
use Awcodes\Curator\CuratorPlugin;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use RyanChandler\FilamentNavigation\FilamentNavigation;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard(env('FILAMENT_AUTH_GUARD', 'staff'))
            ->login()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->plugin(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::make())
            ->plugin(
                CuratorPlugin::make()
                    ->label('Медиафайл')
                    ->pluralLabel('Медиатека')
                    ->navigationLabel('Медиатека')
                    ->navigationGroup('Контент')
                    ->navigationIcon('heroicon-o-photo')
                    ->defaultListView('grid')
            )
            ->plugin(FilamentNavigation::make()
                ->usingResource(NavigationResource::class)
                ->itemType('Страница', [
                    Select::make('url')
                        ->label('Выберите страницу')
                        ->options(fn () => Page::query()
                            ->pluck('title', 'handle')),
                    Select::make('cities')
                        ->label('Города')
                        ->multiple()
                        ->options(fn () => City::pluck('name', 'id')),
                    FileUpload::make('image')
                        ->label('Изображение')
                        ->directory('megamenu')
                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? (array_values($state)[0] ?? null) : $state),
                    TextInput::make('target')->hidden(),
                ], 'page')
                ->itemType('Врачи', [
                    Select::make('id')
                        ->label('Выберите врача')
                        ->options(fn () => Doctor::query()
                            ->pluck('surname', 'id')),
                    Select::make('cities')
                        ->label('Города')
                        ->multiple()
                        ->options(fn () => City::pluck('name', 'id')),
                    FileUpload::make('image')
                        ->label('Изображение')
                        ->directory('megamenu')
                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? (array_values($state)[0] ?? null) : $state),
                    TextInput::make('target')->hidden(),
                ], 'doctor')
                ->itemType('Файл', [
                    FileUpload::make('file')
                        ->label('Файл')
                        ->directory('menu-files')
                        ->required()
                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? (array_values($state)[0] ?? null) : $state),
                    Select::make('cities')
                        ->label('Города')
                        ->multiple()
                        ->options(fn () => City::pluck('name', 'id')),
                    TextInput::make('target')->hidden(),
                ], 'file')
                ->itemType('JS', [
                    TextInput::make('custom-attr')->label('Аттрибут'),
                    Select::make('cities')
                        ->label('Города')
                        ->multiple()
                        ->options(fn () => City::pluck('name', 'id')),
                ])
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
