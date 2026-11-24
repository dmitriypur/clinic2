<div class="space-y-2">
    <x-filament-panels::header
        :actions="$this->getCachedHeaderActions()"
        :breadcrumbs="filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : []"
        :heading="$this->getHeading()"
    />

    @if ($unusedTypes)
        <details>
            <summary class="cursor-pointer text-sm font-medium text-gray-600 dark:text-gray-400">
                Неиспользуемых типов блоков — {{ count($unusedTypes) }}
            </summary>
            <ul class="mt-2 max-h-96 list-inside list-disc overflow-y-auto ps-4 text-sm" tabindex="0">
                @foreach ($unusedTypes as $label)
                    <li>{{ $label }}</li>
                @endforeach
            </ul>
        </details>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-400">Неиспользуемых типов блоков — 0</p>
    @endif
</div>
