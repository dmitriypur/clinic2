<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button wire:click="import">
                    Импортировать
                </x-filament::button>
            </div>
        </x-filament::section>

        @if($this->resultText())
            <x-filament::section heading="Результат импорта">
                <p>{{ $this->resultText() }}</p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
