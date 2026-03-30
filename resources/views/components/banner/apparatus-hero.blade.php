<image-lazy inline-template :eager="true">
    <div class="relative overflow-hidden bg-white" ref="container">
        <div class="hidden md:block absolute top-0 left-1/2 w-[1920px] h-full -translate-x-1/2">
            <picture class="block w-full h-full">
                <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('bg', 'hero')}}' : ''" type="image/webp">
                <img
                    :src="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''"
                    class="w-full h-full object-cover object-right"
                    alt=""
                    aria-hidden="true"
                    fetchpriority="high"
                    loading="eager"
                    decoding="async"
                >
            </picture>
        </div>

        <div class="container relative z-20">
            <div class="md:min-h-[500px] flex flex-col md:justify-center relative">
                <div class="max-w-2xl pt-6 md:pt-14 lg:pt-16">
                    <h1 class="text-4xl md:text-6xl leading-tight md:leading-none font-semibold text-heading">
                        {!! str($block->payload['service_hero_title'] ?? 'Аппаратное лечение зрения у детей')->sanitizeHtml() !!}
                    </h1>

                    <div class="mt-4 md:mt-3 max-w-xl text-base leading-7 text-black">
                        {!! str($block->payload['service_hero_text'] ?? 'Узнайте о новейших технологиях в хирургии катаракты, включая фемтолазерное сопровождение и мультифокальные хрусталики.')->sanitizeHtml() !!}
                    </div>
                </div>

                <div class="mt-4 md:hidden">
                    <picture class="block">
                        <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('pic', 'hero')}}' : ''" type="image/webp">
                        <img
                            :src="isLoaded ? '{{$block->getFirstMediaUrl('pic')}}' : ''"
                            class="w-full h-auto"
                            alt="{{ $block->payload['service_hero_title'] }}"
                            fetchpriority="high"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>

                <div class="absolute bottom-0 w-full md:w-auto md:relative mt-6 pb-6 md:mt-8 md:pb-14">
                    <x-button-primary
                        @click="showCallbackModal(null, 'otpravka-formy')"
                        class="w-full md:w-auto md:min-w-56 rounded-xl text-base px-6 py-4">
                        {{ $block->payload['btn_text'] ?? 'Записаться на приём' }}
                    </x-button-primary>
                </div>
            </div>
        </div>
    </div>
</image-lazy>
