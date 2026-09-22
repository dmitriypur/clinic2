<div class="kids-optics-callback relative overflow-hidden text-white" aria-labelledby="{{ $callbackId }}-title">
    <picture class="kids-optics-callback__pattern pointer-events-none absolute block" aria-hidden="true">
        <source media="(min-width: 768px)" srcset="{{ $desktopPattern }}" type="image/svg+xml">
        <img src="{{ $mobilePattern }}" alt="">
    </picture>

    <picture class="kids-optics-callback__character pointer-events-none absolute block" aria-hidden="true">
        <source media="(min-width: 768px)" srcset="{{ $desktopCharacter }}" type="image/webp">
        <img src="{{ $mobileCharacter }}" alt="">
    </picture>

    <div class="container relative z-10 h-full">
        <div class="kids-optics-callback__content text-left">
            <h2 id="{{ $callbackId }}-title" class="font-semibold">
                Запишитесь на подбор очков онлайн
                <br class="hidden md:block">
                или оставьте заявку на звонок
            </h2>

            <p class="kids-optics-callback__subtitle">
                Оставьте ваши контакты, мы перезвоним вам и подтвердим запись
            </p>

            <div class="kids-optics-callback__actions">
                <x-button-primary
                    type="button"
                    @click="openBookingWidgetV3('otpravka-formy')"
                    class="text-white"
                >
                    Записаться на подбор очков
                </x-button-primary>

                <button
                    type="button"
                    @click="showCallbackFormNew(null, 'otpravka-formy')"
                    class="rounded-xl border border-white font-semibold text-white"
                >
                    <span class="hidden md:inline">Оставить заявку</span>
                    <span class="md:hidden">Заполнить анкету</span>
                </button>
            </div>
        </div>
    </div>
</div>
