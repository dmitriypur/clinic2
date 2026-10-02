<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Фильтры">
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="space-y-1 text-sm">
                    <span>С даты</span>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:bg-gray-900" />
                </label>
                <label class="space-y-1 text-sm">
                    <span>По дату</span>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:bg-gray-900" />
                </label>
                <label class="space-y-1 text-sm">
                    <span>Город</span>
                    <select wire:model.live="cityId" class="w-full rounded-lg border-gray-300 dark:bg-gray-900">
                        <option value="">Все города</option>
                        @foreach($cities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">Всего запросов</p>
                    <p class="text-2xl font-semibold">{{ $totalSearches }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Без результатов</p>
                    <p class="text-2xl font-semibold">{{ $withoutResults }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Популярные запросы">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-2 pr-4">Запрос</th>
                            <th class="py-2 pr-4">Искали</th>
                            <th class="py-2">Без результатов</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topQueries as $item)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $item->query }}</td>
                                <td class="py-2 pr-4">{{ $item->searches }}</td>
                                <td class="py-2">{{ $item->without_results }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-gray-500">Нет запросов за выбранный период.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Запросы без результатов">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-2 pr-4">Запрос</th>
                            <th class="py-2">Сколько раз</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zeroResultQueries as $item)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $item->query }}</td>
                                <td class="py-2">{{ $item->searches }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-3 text-gray-500">Запросов без результатов нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Последние запросы">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-2 pr-4">Дата</th>
                            <th class="py-2 pr-4">Запрос</th>
                            <th class="py-2 pr-4">Город</th>
                            <th class="py-2">Результатов</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentQueries as $item)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $item->created_at->format('d.m.Y H:i') }}</td>
                                <td class="py-2 pr-4">{{ $item->query }}</td>
                                <td class="py-2 pr-4">{{ $item->city?->name ?? '—' }}</td>
                                <td class="py-2">{{ $item->results_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-gray-500">Нет запросов за выбранный период.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $recentQueries->links() }}</div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
