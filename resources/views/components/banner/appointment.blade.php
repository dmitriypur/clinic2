<div class="w-full bg-transparent bg-center bg-no-repeat overflow-hidden relative">
    <image-lazy inline-template :eager="true">
    <div class="absolute top-0 left-0 md:left-[60%] lg:left-[50%] w-full h-full md:w-[1200px] lg:w-[1920px] mx-auto md:-translate-x-1/2 rounded-3xl overflow-hidden" ref="container">
        <picture>
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('pic')}}' : ''" media="(max-width: 767px)">
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''">
            <img :src="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''" class="w-full h-full object-cover" alt="{{ $block->payload['service_hero_title'] }}" fetchpriority="high" loading="eager" decoding="async" width="1920" height="530">
        </picture>
    </div>
    </image-lazy>
    <div class="container relative py-10 px-5 md:pb-20 md:pt-14 z-[1]">
        <div class="flex flex-col items-start relative">
            <h1 class="text-3xl text-interactive font-extrabold leading-none text-center md:text-left md:text-[86px] md:max-w-2xl">{{ $block->payload['service_hero_title'] }}</h1>

            <div class="relative flex items-center justify-center md:justify-start gap-4 md:gap-10 w-full m-auto mt-1 md:mt-7">
                <div class="text-interactive/60"><span class="font-semibold text-lg md:text-5xl line-through">{{ $block->payload['old_price'] }}</span> <span class="text-base md:text-2xl">₽</span></div>
                <div class="text-interactive text-2xl md:text-7xl font-semibold">{{ $block->payload['price'] }} <span class="text-base md:text-5xl">₽</span></div>
                <div class="absolute right-0 -bottom-10 md:relative md:bottom-0 text-sm md:text-base md:relative text-interactive text-nowrap self-end mb-2">{!! $block->payload['service_hero_text'] !!}</div>
            </div>

            <x-button-blue
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="rounded-xl md:w-full mt-48 mx-auto md:mx-0 md:mt-10 md:max-w-56">
                Записаться на приём
            </x-button-blue>
        </div>
    </div>
</div>
