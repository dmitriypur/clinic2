@php
    $trackingBaseUrl = rtrim((string) ($trackingBaseUrl ?? config('app.url')), '/');
    $trackingCitySlug = trim((string) ($trackingCitySlug ?? ''));
    $vkMiniAppId = '54582705';
    $cabinetOptions = \App\Services\UtmTrackerService::CABINETS;
@endphp

<div
    x-data="@include('filament.forms.components.utm-tracker-manager.alpine-data')"
    class="w-full rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
>
    @include('filament.forms.components.utm-tracker-manager.styles')
    @include('filament.forms.components.utm-tracker-manager.toast')
    @include('filament.forms.components.utm-tracker-manager.tabs')

    <div class="p-4">
        @include('filament.forms.components.utm-tracker-manager.tracking-tab')
        @include('filament.forms.components.utm-tracker-manager.archive-tab')
        @include('filament.forms.components.utm-tracker-manager.phones-tab')
    </div>
</div>