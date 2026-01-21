<x-app-layout
    title="Тест нового виджета записи (V2)"
    description="Демонстрация работы нового виджета онлайн-записи V2"
>
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-heading mb-8">
                Демо: Новый виджет онлайн-записи (V2)
            </h1>

            <div class="bg-white rounded-lg shadow-lg p-8 mb-8 border border-gray-100">
                <h2 class="text-2xl font-semibold mb-4 text-interactive">Описание</h2>
                <p class="text-subdued mb-4">
                    Это тестовая страница для нового виджета онлайн-записи, который интегрируется
                    с API adminzrenie.ru для получения данных врачей, расписания и отправки заявок.
                </p>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Мультигород</span>
                    </div>
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Кеширование (быстро)</span>
                    </div>
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Проверка слотов</span>
                    </div>
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Адаптивность</span>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6 border border-orange-200">
                    <h3 class="text-lg font-semibold mb-2">Автоматический выбор</h3>
                    <p class="text-xs text-subdued mb-6">
                        И
спользует город из сессии или первый доступный
                    </p>
                    <button
                        @click="bookingWidgetV2Active = true"
                        class="w-full bg-interactive text-white px-6 py-4 rounded-xl font-bold hover:bg-interactive-button-hovered transition-all shadow-lg active:scale-95"
                    >
                        Записаться на приём
                    </button>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                    <h3 class="text-lg font-semibold mb-2">Выбор города (ID: 1)</h3>
                    <p class="text-xs text-subdued mb-6">
                        Принудительно открывает виджет для города с ID 1
                    </p>
                    <button
                        @click="currentCityId = 1; bookingWidgetV2Active = true"
                        class="w-full bg-blue-600 text-white px-6 py-4 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg active:scale-95"
                    >
                        Записаться в Москву
                    </button>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">Системная информация</h3>
                <div class="grid grid-cols-2 gap-y-2 text-sm">
                    <div class="font-medium text-gray-500">Окружение:</div>
                    <div class="text-right font-mono">{{ config('app.env') }}</div>

                    <div class="font-medium text-gray-500">API Режим:</div>
                    <div class="text-right">
                        @if(config('app.env') === 'production')
                            <span class="text-green-600 font-semibold">Production (adminzrenie.ru)</span>
                        @else
                            <span class="text-orange-600 font-semibold">Development (app.fondzrenie.ru)</span>
                        @endif
                    </div>

                    <div class="font-medium text-gray-500">Текущий город:</div>
                    <div class="text-right font-semibold text-interactive">{{ $city->name ?? 'Не определен' }} (ID: {{ $city->id ?? '?' }})</div>
                </div>
            </div>
        </div>

        <!-- Новый виджет (подключается из app.js) -->
        <booking-widget-v2
            :open="bookingWidgetV2Active"
            :city-id="currentCityId"
            target="demo_booking_widget_v2"
            @close="bookingWidgetV2Active = false"
        ></booking-widget-v2>
    </div>

    @push('scripts')
    <script>
        // Передаем данные о городе для маппинга в виджете по имени
        window.currentCity = {
            id: {{ $city->id ?? 'null' }},
            name: "{{ $city->name ?? 'Москва' }}"
        };

        // Логирование для отладки
        console.log('BookingWidgetV2 Demo: City mapping initialized for', window.currentCity.name);
    </script>
    @endpush
</x-app-layout>
