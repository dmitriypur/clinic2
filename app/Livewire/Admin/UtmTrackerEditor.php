<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Services\UtmTrackerService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Component;
use Throwable;

class UtmTrackerEditor extends Component
{
    public ?City $record = null;

    public ?int $cityId = null;

    public array $state = [];

    public string $trackingBaseUrl = '';

    public string $trackingCitySlug = '';

    public function mount(?City $record = null, ?int $cityId = null): void
    {
        $this->authorizeAccess();

        $this->record = $record;
        $this->cityId = $cityId ?? $record?->id;
        $this->reloadState();
    }

    public function saveState(array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->saveEditorState($city, $state),
            'UTM сохранены',
        );
    }

    public function stopCampaign(string $campaignKey, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->stopCampaign($city, $state, $campaignKey),
            'Кампания остановлена',
        );
    }

    public function launchCampaign(string $campaignKey, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->launchCampaign($city, $state, $campaignKey),
            'Кампания запущена',
        );
    }

    public function launchCampaigns(array $campaignKeys, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->launchCampaigns($city, $state, $campaignKeys),
            'Кампании запущены',
        );
    }

    public function stopCampaigns(array $campaignKeys, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->stopCampaigns($city, $state, $campaignKeys),
            'Кампании остановлены',
        );
    }

    public function resumeCampaign(string $campaignKey, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->resumeCampaign($city, $state, $campaignKey),
            'Кампания возобновлена',
        );
    }

    public function deleteCampaign(string $campaignKey, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->deleteCampaign($city, $state, $campaignKey),
            'Кампания перенесена в архив',
        );
    }

    public function deleteArchivedCampaign(string $campaignKey, array $state): void
    {
        $this->runStateAction(
            fn (City $city, UtmTrackerService $service): array => $service->deleteArchivedCampaign($city, $state, $campaignKey),
            'Архивная кампания удалена',
        );
    }

    public function render()
    {
        return view('filament.forms.components.utm-tracker-manager', [
            'trackingBaseUrl' => $this->trackingBaseUrl,
            'trackingCitySlug' => $this->trackingCitySlug,
        ]);
    }

    private function reloadState(): void
    {
        $this->authorizeAccess();

        $city = $this->getCity();
        $service = app(UtmTrackerService::class);

        $this->state = $service->getEditorState($city);
        $this->trackingBaseUrl = $this->resolveTrackingBaseUrl($city);
        $this->trackingCitySlug = (string) $city->slug;
    }

    private function runStateAction(callable $callback, string $successTitle): void
    {
        $this->authorizeAccess();

        $city = $this->getCity();
        $service = app(UtmTrackerService::class);

        try {
            $this->state = $callback($city, $service);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('UTM не сохранены')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->reloadState();

            return;
        }

        $freshCity = $city->fresh();
        $this->trackingBaseUrl = $this->resolveTrackingBaseUrl($freshCity);
        $this->trackingCitySlug = (string) $freshCity->slug;

        Notification::make()
            ->title($successTitle)
            ->success()
            ->send();
    }

    private function getCity(): City
    {
        if (! $this->cityId) {
            abort(404);
        }

        return City::query()->findOrFail($this->cityId);
    }

    private function authorizeAccess(): void
    {
        abort_unless(Filament::auth()->user()?->can('page_UtmTrackerSettings') ?? false, 403);
    }

    private function resolveTrackingBaseUrl(City $city): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if (! filled($city->slug) || $city->is_default) {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim((string) $city->slug, '/');
    }

}
