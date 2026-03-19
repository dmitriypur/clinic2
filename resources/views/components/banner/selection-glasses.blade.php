<image-lazy inline-template :eager="true">
    <div class="w-full bg-white overflow-hidden relative" ref="container">
        {{-- Фоновое изображение только для десктопа --}}
        <div class="hidden md:block absolute inset-0 w-full h-full">
            <img src="{{ asset('images/hero-bg.jpg') }}" class="w-full h-full object-cover object-center" loading="eager" decoding="async" alt="">
        </div>

        <div class="container relative z-10">
            <div class="flex flex-col md:flex-row items-center md:justify-between md:items-stretch py-10 md:py-0">
                {{-- Левая часть: Текст и Кнопка --}}
                <div class="contents w-full md:block md:w-1/2 md:pr-10 md:pt-32 md:pb-24">
                    <h1 class="order-1 text-heading text-[34px] leading-[1.2] md:text-[42px] lg:text-[64px] font-bold md:leading-[1.1] mb-4 md:mb-6 text-left">
                        {!! str($block->payload['service_hero_title'] ?? 'Подбор очков для зрения ребёнку')->sanitizeHtml() !!}
                    </h1>
                    
                    <div class="order-2 text-heading text-base mb-2 md:mb-8 md:max-w-md text-left">
                        {!! str($block->payload['service_hero_text'] ?? 'Узнайте о новейших технологиях в хирургии катаракты, включая фемтолазерное сопровождение и мультифокальные хрусталики.')->sanitizeHtml() !!}
                    </div>

                    <div class="order-4 w-full md:w-auto flex justify-center md:justify-start -mt-20 md:mt-0 relative z-20">
                        <x-button-primary
                            @click="showCallbackModal(null, 'otpravka-formy')"
                            class="w-full md:w-auto rounded-xl text-base md:text-lg px-8 py-4 min-w-[240px] shadow-lg md:shadow-none">
                            {{ $block->payload['btn_text'] ?? 'Записаться на приём' }}
                        </x-button-primary>
                    </div>
                </div>

                {{-- Правая часть: Изображение --}}
                <div class="order-3 md:order-none w-full md:w-1/2 flex justify-center md:justify-end relative mb-0 md:mb-0 z-10">
                    

                    <picture>
                        <source srcset="{{$block->getFirstMediaUrl('pic')}}" media="(max-width: 767px)">
                        <source srcset="{{$block->getFirstMediaUrl('bg')}}">
                        <img srcset="{{$block->getFirstMediaUrl('bg')}}" class="md:absolute w-full h-auto lg:h-full lg:object-cover xl:object-contain left-0 bottom-0" alt="{{ $block->payload['service_hero_title'] }}" fetchpriority="high" loading="eager" decoding="async">
                    </picture>
                </div>
            </div>
        </div>
    </div>
</image-lazy>
