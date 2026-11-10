<image-lazy inline-template :eager="true">
    <div class="w-full bg-white overflow-hidden relative md:rounded-[24px]" ref="container">
        <div class="hidden md:block absolute inset-0" aria-hidden="true">
            <img src="{{ $block->getFirstMediaUrl('bg') }}" class="w-full h-full object-cover object-center" alt="" fetchpriority="high" loading="eager" decoding="async">
        </div>

        <div class="hidden md:flex relative max-w-[1280px] mx-auto px-4 min-h-[500px] items-center">
            <div class="py-10 max-w-[604px] translate-y-3">
                <h1 class="text-heading text-[42px] lg:text-[64px] leading-[1.2] lg:leading-none font-semibold">
                    {!! str($block->payload['service_hero_title'] ?? 'Подбор очков для зрения ребёнку')->sanitizeHtml() !!}
                </h1>
                <div class="mt-[10px] text-[#1d1d1d] text-base leading-[1.3]">
                    {!! str($block->payload['service_hero_text'] ?? 'Узнайте о новейших технологиях в хирургии катаракты, включая фемтолазерное сопровождение и мультифокальные хрусталики.')->sanitizeHtml() !!}
                </div>
                <div class="mt-[26px]">
                    <x-button-primary @click="showCallbackModal(null, 'otpravka-formy')" class="rounded-[10px] text-base leading-[1.22] px-[30px] py-4">
                        {{ $block->payload['btn_text'] ?? 'Записаться на приём' }}
                    </x-button-primary>
                </div>
            </div>
        </div>

        <div class="container relative z-10 md:hidden">
            <div class="flex flex-col items-center py-10">
                <h1 class="order-1 text-heading text-[34px] leading-[1.2] font-bold mb-4 text-left">
                    {!! str($block->payload['service_hero_title'] ?? 'Подбор очков для зрения ребёнку')->sanitizeHtml() !!}
                </h1>
                <div class="order-2 text-heading text-base mb-2 text-left">
                    {!! str($block->payload['service_hero_text'] ?? 'Узнайте о новейших технологиях в хирургии катаракты, включая фемтолазерное сопровождение и мультифокальные хрусталики.')->sanitizeHtml() !!}
                </div>
                <div class="order-4 w-full flex justify-center -mt-20 relative z-20">
                    <x-button-primary @click="showCallbackModal(null, 'otpravka-formy')" class="w-full rounded-xl text-base px-8 py-4 min-w-[240px] shadow-lg">
                        {{ $block->payload['btn_text'] ?? 'Записаться на приём' }}
                    </x-button-primary>
                </div>
                <div class="order-3 w-full flex justify-center relative mb-0 z-10">
                    <picture>
                        <source srcset="{{ $block->getFirstMediaUrl('pic') }}" media="(max-width: 767px)">
                        <source srcset="{{ $block->getFirstMediaUrl('bg') }}">
                        <img srcset="{{ $block->getFirstMediaUrl('bg') }}" class="w-full h-auto" alt="{{ $block->payload['service_hero_title'] ?? 'Подбор очков для зрения ребёнку' }}" fetchpriority="high" loading="eager" decoding="async">
                    </picture>
                </div>
            </div>
        </div>
    </div>
</image-lazy>
