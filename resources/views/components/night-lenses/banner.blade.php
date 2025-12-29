<div class="w-full bg-blue-dark bg-center bg-no-repeat overflow-hidden relative ">
    <div class="absolute top-0 left-0 md:left-[60%] lg:left-[50%] w-full h-full md:w-[1200px] lg:w-[1920px] mx-auto md:-translate-x-1/2">
        <picture>
            <source srcset="{{$block->getFirstMediaUrl('pic')}}" media="(max-width: 767px)">
            <source srcset="{{$block->getFirstMediaUrl('bg')}}">
            <img srcset="{{$block->getFirstMediaUrl('bg')}}" class="w-full h-full object-cover" alt="{{ $block->payload['service_hero_title'] }}">
        </picture>
    </div>
    <div class="container relative py-12 px-6 md:py-24 z-[1]">
            <div class="flex flex-col items-start">
                <h1 class="text-[40px] md:text-[64px] text-white font-bold leading-[136%] uppercase">{{ $block->payload['service_hero_title'] }}</h1>
                <p @click="showCallbackModal(null, 'otpravka-formy')" class="bg-white/80 border md:border-2 border-white inline-block text-[#165B9F] text-2xl md:text-5xl font-semibold rounded-lg md:rounded-2xl py-2 px-7 md:py-6 md:px-20 mt-2 md:mt-10 md:order-3 cursor-pointer">{{ $block->payload['service_hero_text'] }}</p>
                <p class="text-lg md:text-[40px] text-white font-medium leading-[128%] mt-52 md:mt-4">{!! $block->payload['service_hero_subtitle'] !!}</p>
            </div>
    </div>
</div>
