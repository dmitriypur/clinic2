@extends('layouts.app')

@section('title', 'Тест нового виджета записи')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-heading mb-8">
            Демо: Новый виджет онлайн-записи (V2)
        </h1>

        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Описание</h2>
            <p class="text-subdued mb-4">
                Это тестовая страница для нового виджета онлайн-записи, который интегрируется 
                с API adminzrenie.ru для получения данных врачей, расписания и отправки заявок.
            </p>

            <div class="space-y-2 text-subdued">
                <p>✅ Поддержка мультигорода</p>
                <p>✅ Быстрая загрузка данных (кеширование)</p>
                <p>✅ Проверка доступности слотов</p>
                <p>✅ Адаптивный дизайн</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Базовый вариант</h3>
                <p class="text-sm text-subdued mb-4">
                    Открывает виджет с автоматическим определением города
                </p>
                <button
                    @click="bookingWidgetV2Active = true"
                    class="w-full bg-interactive text-white px-6 py-3 rounded-lg font-semibold hover:bg-interactive-button-hovered transition-colors shadow-md"
                >
                    Записаться на приём
                </button>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">С указанием города</h3>
                <p class="text-sm text-subdued mb-4">
                    Открывает виджет для конкретного города
                </p>
                <button
                    @click="openWidgetWithCity(1)"
                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-md"
                >
                    Записаться (Город ID: 1)
                </button>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold mb-4">Информация о текущей конфигурации</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="font-medium">Режим:</dt>
                    <dd class="text-subdued">{{ config('app.env') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">API URL:</dt>
                    <dd class="text-subdued font-mono text-xs">
                        {{ config('app.env') === 'production' ? 'adminzrenie.ru/api/v1' : 'app.fondzrenie.ru/api/v1' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Текущий город:</dt>
                    <dd class="text-subdued">{{ $city->name ?? 'Не определен' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Сам виджет -->
    <booking-widget-v2
        :open="bookingWidgetV2Active"
        :city-id="currentCityId"
        target="demo_booking_widget_v2"
        @close="bookingWidgetV2Active = false"
    />
</div>

<script>
    // Передаем данные в Vue
    window.currentCity = {
        id: {{ $city->id ?? 1 }},
        name: '{{ $city->name ?? 'Москва' }}'
    };

    // Можно расширить Vue instance для этой страницы
    if (window.vueApp) {
        window.vueApp.$data.currentCityId = {{ $city->id ?? 1 }};
        
        window.vueApp.openWidgetWithCity = function(cityId) {
            this.currentCityId = cityId;
            this.bookingWidgetV2Active = true;
        };
    }
</script>
@endsection
