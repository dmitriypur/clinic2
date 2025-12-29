<div class="w-full bg-action-primary bg-center bg-no-repeat overflow-hidden relative ">
    <image-lazy inline-template>
    <div class="absolute top-0 left-0 md:left-[60%] lg:left-[50%] w-full h-full md:w-[1200px] lg:w-[1920px] mx-auto md:-translate-x-1/2" ref="container">
        <picture>
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('pic')}}' : ''" media="(max-width: 767px)">
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''">
            <img :src="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''" class="w-full h-full object-cover" alt="{{ $block->payload['service_hero_title'] }}" width="1920" height="530">
        </picture>
    </div>
    </image-lazy>
    <div class="container relative py-8 px-5 md:py-14 z-[1]">
        <div class="flex flex-col items-start relative">
            <h1 class="text-[40px] text-white font-bold leading-[136%] uppercase tracking-tight md:tracking-normal md:text-[64px] md:max-w-xl">{{ $block->payload['service_hero_title'] }}</h1>

            <div class="flex flex-col md:flex-row md:gap-x-16 md:items-center md:mt-7">
                <p class="text-5xl text-white font-bold mt-12 md:mt-0 md:text-7xl">{{ $block->payload['service_hero_subtitle'] }}</p>
                <div class="mt-6 md:mt-0 relative">
                    <div class="text-white/50 text-nowrap text-center text-xl md:text-3xl md:absolute md:top-1/2 md:-translate-y-1/2 md:-right-40">{{ $block->payload['old_price'] }} ₽</div>
                    <p class="bg-white text-[#0084FF] text-2xl md:text-4xl font-semibold rounded-xl py-1.5 px-9 md:py-4 md:px-12 md:rounded-2xl">{{ $block->payload['price'] }} ₽</p>
                    <img src="{{ asset('images/percent.webp') }}" alt="Знак процента — иллюстрация скидки, акции" width="80" height="80" class="absolute -bottom-1 md:-bottom-2 -right-7 md:-right-12 w-14 md:w-20">
                </div>
            </div>
            <p class="absolute right-2 lg:right-20 bottom-16 lg:bottom-10 text-lg lg:text-3xl italic text-white font-medium lg:font-bold text-nowrap">{!! $block->payload['service_hero_text'] !!}</p>

            <x-button-blue
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="rounded-xl w-full text-base md:text-2xl min-h-14 mt-20 md:mt-14 md:py-7 md:max-w-xl">
                Записаться на приём
            </x-button-blue>
        </div>
    </div>
</div>
