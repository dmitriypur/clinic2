<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Illuminate\Http\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\PdfToImage\Pdf;

class ManageGeneralSettings extends SettingsPage
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $slug = 'settings/general';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Основные настройки';

    protected static ?string $title = 'Основные настройки';

    protected static string $settings = GeneralSettings::class;

    protected static ?int $navigationSort = 7;

    public function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->disabled(auth()->user()->hasRole('demo'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->disabled(auth()->user()->hasRole('demo'))
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Настройки')
                            ->schema([
                                Forms\Components\Section::make('Основная информация')->schema([
                                    TextInput::make('site_name')->label('Название сайта')->required(),
                                ]),

                                Forms\Components\Section::make('Продвижение сайта')->schema([
                                    TextInput::make('promotion_company')->label('Название компании'),
                                    TextInput::make('promotion_company_url')->label('Ссылка'),
                                ]),

                                Forms\Components\Section::make('Лицензии')->schema([
                                    Forms\Components\FileUpload::make('licenses')
                                        ->acceptedFileTypes(['application/pdf', 'image/*',])
                                        ->multiple()
                                        ->label('Файл'),
                                ]),

                                Forms\Components\Section::make('Favicon')->schema([
                                    Forms\Components\FileUpload::make('favicon')
                                        ->acceptedFileTypes(['image/png', 'image/svg+xml'])
                                        ->imageResizeMode('cover')
                                        ->imageCropAspectRatio('1:1')
                                        ->imageResizeTargetWidth('120')
                                        ->imageResizeTargetHeight('120')
                                        ->label('favicon'),
                                ]),

                                Forms\Components\Section::make('API-ключ яндекс-карт')->schema([
                                    TextInput::make('yandex_map_api_key')
                                        ->label('API-ключ яндекс-карт')
                                        ->helperText(new HtmlString('Зайдите на страницу <a href="https://developer.tech.yandex.ru/" target="_blank">Кабинета Разработчика</a> и нажмите кнопку Получить ключ. Во всплывающем окне выберите сервис «JavaScript API и HTTP Геокодер».'))
                                        ->hint(new HtmlString('<a href="https://developer.tech.yandex.ru/" target="_blank">Кабинета Разработчика Яндекс</a>'))
                                        ->required(),
                                ]),
                            ]),
                    ])->columnSpanFull()

            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['licenses'] = collect($data['licenses'])
            ->map(function ($item) {
                $file = new File(storage_path('app/public/' . $item));

                if ($file->getMimeType() !== 'application/pdf') {
                    return $item;
                }

                $name = Str::before($item, '.pdf');
                $pdf = new Pdf(storage_path('app/public/' . $item));
                $numberOfPages = $pdf->getNumberOfPages();
                $images = [];

                foreach (range(1, $numberOfPages) as $index) {
                    $fileName = $name . $index . ' .jpg';
                    $pdf->setPage($index)
                        ->saveImage(storage_path('app/public/' . $fileName));
                    $images[] = $fileName;
                }

                return $images;
            })
            ->flatten()
            ->filter(fn($item) => !Str::endsWith($item, '.pdf'))
            ->toArray();

        return $data;
    }
}
