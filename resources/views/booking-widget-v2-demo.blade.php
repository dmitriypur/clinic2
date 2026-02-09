<x-app-layout
    title="Тест нового виджета записи (V2)"
    description="Демонстрация работы нового виджета онлайн-записи V2"
>
<!-- Backdrop -->
<!-- Backdrop -->
<div class="bg-[#98A4B7] flex items-center justify-center p-4">
  <!-- Modal (макет: ~964px ширина, rounded 24) -->
  <section class="relative w-full max-w-[964px] rounded-[24px] bg-white overflow-hidden">
    <!-- top divider line (как в макете) -->
    <div class="absolute left-0 top-[101px] h-px w-full bg-[#EBF0F3]"></div>

    <!-- Close -->
    <button
      class="absolute right-6 top-6 grid h-10 w-10 place-items-center rounded-xl text-[#1F3462] hover:bg-slate-50"
      aria-label="Close"
    >
      ✕
    </button>

    <!-- Title -->
    <div class="flex items-center">
        <h2 class="px-10 pt-10 text-[34px] leading-[1.2] font-semibold text-[#1F3462]">
        Выберите дату, время и филиал
        </h2>

        <!-- Chip (Макулотестер) -->
        <div class="bg-[#F6F7F9] px-5 py-[5px] mt-10 text-[12px] leading-[1.2] text-[#1D1D1D] shadow-[0px_0px_1.8px_rgba(31,52,98,0.26)]">
            Шаг №3
        </div>
    </div>

    <!-- Content -->
    <div class="px-10 pb-10 pt-8">
      <div class="grid grid-cols-1 lg:grid-cols-[400px_24px_444px] gap-0 items-start">
        <!-- LEFT -->
        <div class="relative">
          <!-- Selected doctor card (top) -->
          <div class="flex items-center gap-[25px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-2">
            <div class="h-[60px] w-[60px] overflow-hidden rounded-[8px] bg-white">
              <!-- avatar -->
              <img
                src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=300&auto=format&fit=crop"
                alt=""
                class="h-full w-full object-cover"
              />
            </div>

            <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462] whitespace-pre-line">
              Брязгина Анастасия
              Александровна
            </p>
          </div>

          <!-- Branch list title -->
          <div class="mt-8 text-center text-[20px] leading-[1.2] font-semibold text-[#1F3462]">
            Доступные филиалы
          </div>

          <!-- Branches -->
<div class="relative mt-[7px] h-[388px]">
  <!-- SCROLL AREA -->
  <div
    class="h-full overflow-y-auto pr-2 space-y-[7px]"
    style="scrollbar-gutter: stable;"
  >
    <!-- item (disabled) -->
    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px] opacity-40">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <!-- item (selected) -->
    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-[#EBF0F3] px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <!-- ...ещё элементы -->
    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px] opacity-40">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px] opacity-40">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <!-- item (selected) -->
    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-[#EBF0F3] px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <!-- ...ещё элементы -->
    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px]">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>

    <div class="h-[64px] rounded-[12px] border-2 border-[#EBF0F3] bg-white px-6 py-[13px] opacity-40">
      <p class="text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
        г. Москва, ул. Название улицы, д. 38
      </p>
    </div>
  </div>

  <!-- bottom fade -->
  <div class="pointer-events-none absolute bottom-0 left-0 h-[71px] w-full bg-gradient-to-b from-white/0 to-white"></div>
</div>

        </div>

        <!-- MIDDLE: scroll bar (как в макете) -->
        <div class="hidden lg:flex justify-center pt-[133px]">
          <div class="hidden h-[24px] w-[4px] rounded-[28px] bg-[#1F3462]"></div>
        </div>

        <!-- RIGHT -->
        <div class="pt-[34px] lg:pt-0">
          <!-- Month header -->
          <div class="flex items-center justify-center gap-4">
            <button class="grid h-8 w-8 place-items-center rounded-lg text-[#1F3462] hover:bg-slate-50" aria-label="Prev">
              ‹
            </button>

            <div class="text-[20px] leading-[1.2] font-semibold text-[#1F3462]">
              Март 2025
            </div>

            <button class="grid h-8 w-8 place-items-center rounded-lg text-[#1F3462] hover:bg-slate-50" aria-label="Next">
              ›
            </button>
          </div>

          <!-- Weekdays -->
          <div class="mt-[27px] grid grid-cols-7 gap-1">
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Пн</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Вт</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Ср</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Чт</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Пт</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Сб</div>
            <div class="h-[27px] w-[60px] rounded-[4px] bg-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">Вс</div>
          </div>

          <!-- Days grid (60x27, border EBf0f3) -->
          <div class="mt-1 space-y-1">
            <!-- row helper -->
            <div class="grid grid-cols-7 gap-1">
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">1</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">2</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">3</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">4</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">5</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">6</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">7</button>
            </div>

            <div class="grid grid-cols-7 gap-1">
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">8</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">9</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">10</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">11</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">12</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">13</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">14</button>
            </div>

            <div class="grid grid-cols-7 gap-1">
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">15</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-black/30">16</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">17</button>

              <!-- active day (радиальный градиент как в макете) -->
              <button
                class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-white
                       bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]"
              >18</button>

              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">19</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">20</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">21</button>
            </div>

            <div class="grid grid-cols-7 gap-1">
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">22</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">23</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">24</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">25</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">26</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">27</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">28</button>
            </div>

            <div class="grid grid-cols-7 gap-1">
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">29</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">30</button>
              <button class="h-[27px] w-[60px] rounded-[4px] border border-[#EBF0F3] grid place-items-center text-[16px] font-semibold text-[#1F3462]">31</button>
              <div></div><div></div><div></div><div></div>
            </div>
          </div>

          <!-- divider line under calendar -->
          <div class="mt-6 h-px w-full bg-[#EBF0F3]"></div>

          <!-- Time title -->
          <div class="mt-6 text-center text-[16px] leading-[1.2] font-semibold text-[#1F3462]">
            Время
          </div>

          <!-- Times (w 85, h 27) -->
          <div class="mt-4 flex flex-wrap gap-1 w-[444px] max-w-full justify-center">
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">9:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">10:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">11:00</button>

            <button
              class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-white
                     bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]"
            >12:00</button>

            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">13:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">14:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">15:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">16:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">17:00</button>
            <button class="h-[27px] w-[85px] rounded-[4px] border border-[#EBF0F3] text-[16px] font-semibold text-[#1F3462]">18:00</button>
          </div>

          <!-- Bottom buttons (gap 16, each flex-1, rounded 10, py16 px30) -->
          <div class="mt-10 flex gap-4">
            <button class="flex-1 rounded-[10px] border border-[#1F3462] px-[30px] py-4 text-[16px] font-semibold text-[#1F3462]">
              Записаться на приём
            </button>

            <button
              class="flex-1 rounded-[10px] px-[30px] py-4 text-[16px] font-semibold text-white
                     bg-[radial-gradient(ellipse_at_center,_rgba(255,164,118,1)_0%,_rgba(255,150,89,1)_25%,_rgba(255,135,59,1)_50%,_rgba(255,121,30,1)_75%,_rgba(255,113,15,1)_87.5%,_rgba(255,106,0,1)_100%)]"
            >
              Записаться на приём
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>


    <div class="hidden container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-heading mb-8">
                Демо: Новый виджет онлайн-записи (V2)
            </h1>

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
