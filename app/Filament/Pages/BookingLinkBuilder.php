<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Services\BookingLinkBuilderService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;

class BookingLinkBuilder extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Конструктор ссылок записи';

    protected static ?string $title = 'Конструктор ссылок записи';

    protected static ?string $slug = 'booking-links';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.booking-link-builder';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return ($user?->can('page_BookingLinkBuilder') ?? false)
            || ($user?->can('page_BookingWidgetSettings') ?? false)
            || ($user?->hasRole('super_admin') ?? false);
    }

    public function mount(BookingLinkBuilderService $linkBuilder): void
    {
        $cityId = $this->getDefaultCityId();

        $this->form->fill([
            'city_id' => $cityId,
            'page_id' => '__home',
            'entry' => 'doctor',
            'doctor_id' => $this->firstOptionKey($this->doctorOptions($cityId, $linkBuilder)),
            'branch_id' => $this->firstOptionKey($this->branchOptions($cityId, $linkBuilder)),
            'utm_key' => $this->firstOptionKey($this->utmOptions($cityId, $linkBuilder)),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('city_id')
                            ->label('Город')
                            ->options(fn (): array => City::query()
                                ->where('active', true)
                                ->orderByDesc('is_default')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $linkBuilder = app(BookingLinkBuilderService::class);
                                $cityId = is_numeric($state) ? (int) $state : null;

                                $set('page_id', '__home');
                                $set('doctor_id', $this->firstOptionKey($this->doctorOptions($cityId, $linkBuilder)));
                                $set('branch_id', $this->firstOptionKey($this->branchOptions($cityId, $linkBuilder)));
                                $set('utm_key', $this->firstOptionKey($this->utmOptions($cityId, $linkBuilder)));
                            }),

                        Select::make('page_id')
                            ->label('Страница')
                            ->options(fn (Get $get): array => $this->pageOptions($this->numericState($get('city_id')), app(BookingLinkBuilderService::class)))
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->required(),

                        Select::make('entry')
                            ->label('Тип ссылки')
                            ->options([
                                'doctor' => 'Врач',
                                'branch' => 'Филиал',
                            ])
                            ->native(false)
                            ->live()
                            ->required(),

                        Select::make('doctor_id')
                            ->label('Врач')
                            ->options(fn (Get $get): array => $this->doctorOptions($this->numericState($get('city_id')), app(BookingLinkBuilderService::class)))
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('entry') === 'doctor')
                            ->required(fn (Get $get): bool => $get('entry') === 'doctor'),

                        Select::make('branch_id')
                            ->label('Филиал')
                            ->options(fn (Get $get): array => $this->branchOptions($this->numericState($get('city_id')), app(BookingLinkBuilderService::class)))
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('entry') === 'branch')
                            ->required(fn (Get $get): bool => $get('entry') === 'branch')
                            ->helperText('Филиалы берутся из списка настроек виджета. Если список пустой, сначала откройте “Настройки виджета” для обновления филиалов.'),

                        Select::make('utm_key')
                            ->label('UTM')
                            ->options(fn (Get $get): array => [
                                '' => 'Без UTM',
                                ...$this->utmOptions($this->numericState($get('city_id')), app(BookingLinkBuilderService::class)),
                            ])
                            ->native(false)
                            ->searchable()
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function getGeneratedUrl(): string
    {
        $linkBuilder = app(BookingLinkBuilderService::class);
        $city = $this->selectedCity();
        $entry = (string) data_get($this->data, 'entry', 'doctor');
        $targetId = $entry === 'doctor'
            ? data_get($this->data, 'doctor_id')
            : data_get($this->data, 'branch_id');

        if (! $city || blank($targetId)) {
            return '';
        }

        return $linkBuilder->buildUrl(
            city: $city,
            page: $linkBuilder->findPageByOption(data_get($this->data, 'page_id')),
            entry: $entry,
            targetId: (string) $targetId,
            utm: $linkBuilder->getUtmPayloadByKey($city, $this->selectedUtmKey($city, $linkBuilder)),
        );
    }

    private function selectedUtmKey(City $city, BookingLinkBuilderService $linkBuilder): ?string
    {
        $utmKey = data_get($this->data, 'utm_key');

        if ($utmKey === '') {
            return null;
        }

        if (filled($utmKey)) {
            return (string) $utmKey;
        }

        return $this->firstOptionKey($linkBuilder->getUtmOptions($city));
    }

    private function selectedCity(): ?City
    {
        $cityId = $this->numericState(data_get($this->data, 'city_id'));

        return $cityId ? City::query()->find($cityId) : null;
    }

    private function getDefaultCityId(): ?int
    {
        return City::query()
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->value('id');
    }

    private function pageOptions(?int $cityId, BookingLinkBuilderService $linkBuilder): array
    {
        $city = $cityId ? City::query()->find($cityId) : null;

        return $city ? $linkBuilder->getPageOptions($city) : ['__home' => 'Главная'];
    }

    private function doctorOptions(?int $cityId, BookingLinkBuilderService $linkBuilder): array
    {
        $city = $cityId ? City::query()->find($cityId) : null;

        return $city ? $linkBuilder->getDoctorOptions($city) : [];
    }

    private function branchOptions(?int $cityId, BookingLinkBuilderService $linkBuilder): array
    {
        $city = $cityId ? City::query()->find($cityId) : null;

        return $city ? $linkBuilder->getBranchOptions($city) : [];
    }

    private function utmOptions(?int $cityId, BookingLinkBuilderService $linkBuilder): array
    {
        $city = $cityId ? City::query()->find($cityId) : null;

        return $city ? $linkBuilder->getUtmOptions($city) : [];
    }

    private function firstOptionKey(array $options): ?string
    {
        $key = array_key_first($options);

        return $key === null ? null : (string) $key;
    }

    private function numericState(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
