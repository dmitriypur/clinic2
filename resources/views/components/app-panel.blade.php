<div class="hidden rounded-2xl bg-surface max-w-[367px] md:flex flex-col px-6 py-7">
    <x-button-primary @click="showCallbackModal(null, 'otpravka-formy')" class="h-20 !rounded-xl text-xl">
        Записаться на приём
    </x-button-primary>

    <div class="mt-9">
        <a href="https://yandex.ru/maps/?rtext=~{{ $currentCity->coordinates }}" rel="nofollow" target="_blank" class="flex items-center gap-10 px-6 py-8 bg-surface-subdued rounded-2xl">
            <span class="text-2xl font-medium">Построить маршрут</span>
            <x-icon-route/>
        </a>
        <a href="https://taxi.yandex.ru/ru_ru?gfrom=~&gto={{ $currentCity->coordinates }}&level&ref=2334695&tariff&referrer=appmetrica_tracking_id%3D241755468559577482%26ym_tracking_id%3D10142476165914361925" rel="nofollow" target="_blank" class="flex items-center gap-10 px-6 py-8 bg-surface-subdued rounded-2xl mt-6">
            <span class="text-2xl font-medium">Вызвать такси</span>
            <x-icon-auto/>
        </a>
    </div>
</div>
