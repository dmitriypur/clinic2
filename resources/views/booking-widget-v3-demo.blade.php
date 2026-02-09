<x-app-layout
    title="Тест нового виджета записи (V3)"
    description="Демонстрация работы нового виджета онлайн-записи V3"
>
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-heading mb-6">
                Демо: Новый виджет онлайн-записи (V3)
            </h1>

            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 mb-8">
                <p class="text-sm text-subdued mb-4">
                    Эта страница доступна только в dev-окружении. Открывает новую форму записи в модальном окне.
                </p>
                <button
                    @click="bookingWidgetV3Active = true"
                    class="w-full bg-interactive text-white px-6 py-4 rounded-xl font-bold hover:bg-interactive-button-hovered transition-all shadow-lg active:scale-95"
                >
                    Открыть форму V3
                </button>
            </div>
        </div>

        <booking-widget-v3
            :open="bookingWidgetV3Active"
            @close="bookingWidgetV3Active = false"
        ></booking-widget-v3>
    </div>

    @push('scripts')
    <script>
        window.currentCity = {
            id: {{ $city->id ?? 'null' }},
            name: "{{ $city->name ?? 'Москва' }}"
        };
    </script>
    @endpush
</x-app-layout>
