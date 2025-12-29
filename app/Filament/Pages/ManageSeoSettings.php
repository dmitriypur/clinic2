<?php

namespace App\Filament\Pages;

use App\Jobs\RegenerateSitemap;
use App\Settings\SeoSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageSeoSettings extends SettingsPage
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $slug = 'settings/seo';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки для SEO';

    protected static ?string $title = 'Настройки для SEO';

    protected static string $settings = SeoSettings::class;

    protected static ?int $navigationSort = 8;

    public function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->disabled(auth()->user()->hasRole('demo'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Forms\Components\Section::make('Редактирование robots.txt')->schema([
                    Forms\Components\Textarea::make('robots_txt')->label('robots.txt')->required(),
                ]),

                Forms\Components\Section::make('Скрипты в head')->schema([
                    Forms\Components\Repeater::make('header_scripts')->label('Скрипты (метрика и т.д.)')
                        ->schema([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\Textarea::make('value')->required(),
                        ]),
                ]),

                Forms\Components\Section::make("Скрипты перед </body>")->schema([
                    Forms\Components\Repeater::make('scripts')->label('Скрипты (метрика и т.д.)')
                        ->schema([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\Textarea::make('value')->required(),
                        ]),
                ]),

                Forms\Components\Section::make('Sitemap')->schema([
                    Forms\Components\Checkbox::make('ignore_sitemap_last_mode')
                        ->label('Исключить тег LastMod из sitemap.xls'),
                ]),

                Forms\Components\Section::make('Логотип')->schema([
                    Forms\Components\TextInput::make('logo_alt')
                        ->label('Alt'),

                    Forms\Components\TextInput::make('logo_title')
                        ->label('Title'),
                ]),

                Forms\Components\Section::make('Изображения')->schema([
                    Forms\Components\TextInput::make('image_alt_template')
                        ->label('Шаблон для Alt')
                        ->helperText('Допустимые значения: {h1}'),

                    Forms\Components\TextInput::make('image_title_template')
                        ->label('Шаблон для Title')
                        ->helperText('Допустимые значения: {h1}'),
                ])
            ]);
    }

    public function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('regenerateSitemap')
                ->label('Сохранить и обновить sitemap.xml')
                ->action('regenerateSitemap')
                ->color('secondary'),
        ];
    }

    public function regenerateSitemap(): void
    {
        $this->callHook('beforeValidate');

        $data = $this->form->getState();

        $this->callHook('afterValidate');

        $data = $this->mutateFormDataBeforeSave($data);

        $this->callHook('beforeSave');

        $settings = app(static::getSettings());

        $settings->fill($data);
        $settings->save();

        dispatch(new RegenerateSitemap);

        $this->callHook('afterSave');

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl);
        }
    }
}
