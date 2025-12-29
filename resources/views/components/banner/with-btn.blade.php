<image-lazy inline-template>
<div class="w-full bg-center bg-no-repeat overflow-hidden relative md:max-h-[510px]" ref="container">

    <div class="absolute top-0 left-[60%] lg:left-[50%] w-full h-full md:w-[1200px] lg:w-[1920px] mx-auto -translate-x-1/2" >
        <picture>
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('pic')}}' : ''" media="(max-width: 767px)">
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''">
            <img :src="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''" class="w-full h-full object-cover" alt="{{ $block->payload['service_hero_title'] }}">
        </picture>
    </div>
    <div class="container relative py-8 md:py-20 z-[1]">
            <div class="relative">
                <h1 class="text-[44px] md:text-[64px] md:text-left font-bold leading-[110%] uppercase">{{ $block->payload['service_hero_title'] }}</h1>
                <p class="tracking-[-0.4px] text-[20px] md:text-[32px] md:text-left font-semibold leading-[110%] mt-[7px]">{{ $block->payload['service_hero_subtitle'] }}</p>
                <p class="text-[28px] max-w-[240px] md:max-w-full md:text-[40px] font-bold leading-[120%] mt-[45px] md:mt-[30px] [&_span]:text-action-primary ">{!! $block->payload['service_hero_text'] !!}</p>
                <div class="mt-4 md:absolute left-[465px] bottom-[40px]">
                    <div class="py-3 px-[39px] md:px-[65px] bg-blue-label text-white [text-shadow:_0_0_15px_rgb(255_255_255)] rounded-xl inline-block text-[24px] md:text-[36px] font-bold relative whitespace-nowrap">
                        <img :src="isLoaded ? '{{ asset('images/percent.png') }}' : ''" alt="" class="absolute top-[-25px] right-[-25px] md:top-[-20px] md:right-[-75px] w-[57px] md:w-[130px]">
                        <span class="hidden md:block md:absolute md:text-interactive opacity-50 -top-14 right-6 font-normal"><s>{{ $block->payload['old_price'] }} ₽</s></span>
                        {{ $block->payload['price'] }} ₽
                    </div>
                    <x-hero-text class="md:hidden"></x-hero-text>
                </div>
                <div class="overflow-hidden rounded-2xl mt-[7px] md:mt-[83px] md:w-[425px] outline-2 -outline-offset-2 hover:outline">
                    <x-button-primary
                        @click="showCallbackModal(null, 'otpravka-formy')"
                        class="w-full h-[72px] text-[20px] md:h-[75px] md:text-[24px] font-bold">
                        Купить по специальной цене
                    </x-button-primary>
                </div>

            </div>
        <img :src="isLoaded ? '{{asset('images/hero-firefox.png')}}' : ''" alt="лис" width="220" class="hidden lg:block lg:absolute lg:bottom-0 lg:right-0 xl:right-28">
    </div>
</div>
</image-lazy>
