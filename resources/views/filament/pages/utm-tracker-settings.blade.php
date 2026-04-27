<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if ($this->getSelectedCityId())
            <x-filament::section>
                <x-slot name="heading">
                    UTM Трекер{{ $this->getSelectedCityName() ? ': ' . $this->getSelectedCityName() : '' }}
                </x-slot>

                @livewire(
                    \App\Livewire\Admin\UtmTrackerEditor::class,
                    ['cityId' => $this->getSelectedCityId()],
                    key('utm-tracker-' . $this->getSelectedCityId())
                )
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
