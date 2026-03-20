<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if ($this->getSelectedCityId())
            <x-filament::section heading="Врачи" description="Порядок задаётся отдельно для каждого города и сценария. На втором шаге ветки «Выбрать врача» и на третьем шаге ветки «Выбрать клинику» можно использовать разный порядок. Меньше число — выше в списке.">
                @livewire(
                    \App\Livewire\Admin\BookingWidgetDoctorsTable::class,
                    ['cityId' => $this->getSelectedCityId()],
                    key('booking-widget-doctors-' . $this->getSelectedCityId())
                )
            </x-filament::section>

            <x-filament::section heading="Филиалы" description="Список филиалов подтягивается из booking API автоматически. Меньше число — выше в списке.">
                @livewire(
                    \App\Livewire\Admin\BookingWidgetBranchesTable::class,
                    ['cityId' => $this->getSelectedCityId()],
                    key('booking-widget-branches-' . $this->getSelectedCityId())
                )
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
