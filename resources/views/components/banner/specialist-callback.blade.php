<div class="relative overflow-hidden orange-gr-nohover">
    <div class="container relative z-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between pt-12 md:py-0">
            <div class="w-full text-center md:text-left md:py-16">
                <h2 class="text-3xl md:text-4xl leading-tight font-semibold text-white">
                    Запишитесь на приём специалиста онлайн
                    <br class="hidden md:block">
                    или оставьте заявку на звонок
                </h2>

                <div class="mx-auto mt-6 max-w-md text-base leading-normal text-white md:mx-0 md:max-w-2xl">
                    Оставьте ваши контакты, мы перезвоним вам и подтвердим запись
                </div>

                <div class="mt-8 flex flex-col gap-6 md:flex-row md:items-center md:max-w-[484px]">
                    <x-button-blue
                        @click="openBookingWidgetV3('otpravka-formy')"
                        class="w-full rounded-xl py-4 text-base shadow-none md:min-w-56">
                        Записаться на приём
                    </x-button-blue>

                    <button
                        @click="showCallbackFormNew(null, 'otpravka-formy')"
                        class="w-full rounded-xl border border-white px-6 py-4 text-center text-base font-semibold leading-tight text-white md:min-w-56">
                        Оставить заявку
                    </button>
                </div>
            </div>

            <div class="md:absolute top-0 -right-40 h-full -z-10 max-w-4xl -mx-4 md:mx-0">
                <picture>
                    <source
                        srcset="{{ asset('images/specialist-callback-mobile.webp') }}"
                        media="(max-width: 767px)"
                    >
                    <img
                        src="{{ asset('images/specialist-callback-desc.webp') }}"
                        alt="Запишитесь на приём специалиста онлайн или оставьте заявку на звонок"
                        class="block w-full h-full object-contain"
                        loading="eager"
                        decoding="async"
                    >
                </picture>
            </div>
        </div>
    </div>
</div>
