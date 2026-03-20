<div class="space-y-4" wire:init="syncBranchesIfNeeded">
    <div wire:loading.flex wire:target="syncBranchesIfNeeded" class="text-sm text-gray-500">
        Обновляем список филиалов из booking API...
    </div>

    @if ($syncError)
        <x-filament::section>
            <p class="text-sm text-danger-600">{{ $syncError }}</p>
        </x-filament::section>
    @endif

    {{ $this->table }}
</div>
