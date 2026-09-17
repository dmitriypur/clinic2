<?php

namespace App\Livewire\Admin\Concerns;

use App\Filament\Pages\BookingWidgetSettings;
use App\Models\City;
use Filament\Facades\Filament;

trait AuthorizesBookingWidgetSettings
{
    protected function authorizeBookingWidgetSettingsAccess(bool $mutation = false): City
    {
        $staff = Filament::auth()->user();

        abort_unless($staff !== null && BookingWidgetSettings::canAccess(), 403);
        abort_if($mutation && $staff->hasRole('demo'), 403);

        $city = City::query()
            ->whereKey($this->cityId)
            ->where('active', true)
            ->first();

        abort_if($city === null, 404);

        return $city;
    }

    protected function canMutateBookingWidgetSettings(): bool
    {
        $staff = Filament::auth()->user();

        return $staff !== null
            && BookingWidgetSettings::canAccess()
            && ! $staff->hasRole('demo')
            && City::query()->whereKey($this->cityId)->where('active', true)->exists();
    }
}
