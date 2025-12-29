<div class="w-full bg-blue-dark bg-center bg-no-repeat overflow-hidden relative ">
    <div class="absolute top-0 left-0 md:left-[50%] w-full h-full md:w-[1200px] lg:w-[1920px] 2xl:w-full mx-auto md:-translate-x-1/2">
        <picture>
            <source srcset="{{$block->getFirstMediaUrl('pic')}}" media="(max-width: 767px)">
            <source srcset="{{$block->getFirstMediaUrl('bg')}}">
            <img srcset="{{$block->getFirstMediaUrl('bg')}}" class="w-full h-full object-cover" alt="{{ $block->payload['service_hero_title'] }}">
        </picture>
    </div>
    <div class="container relative py-12 md:py-20">
        <div class="md:w-8/12 lg:w-7/12">
            <div class="flex flex-col items-start relative">
                <h1 class="text-white text-3xl lg:text-55 lg:leading-14 lg:tracking-between uppercase font-semibold [&_span]:block [&_span]:font-medium">{!! str($block->payload['service_hero_title'])->sanitizeHtml() !!}</h1>
                <p class="max-w-64 md:max-w-full font-medium text-white text-sm md:text-lg lg:text-xl tracking-between pr-11 [&_b]:font-semibold [&_span]:block [&_span]:md:inline mt-6 lg:mt-10">{!! str($block->payload['service_hero_text'])->sanitizeHtml() !!}</p>
                <x-button-primary
                    @click="showCallbackModal(null, 'otpravka-formy')"
                    class="mt-24 lg:mt-10 rounded-xl w-full max-w-60 md:max-w-sm lg:max-w-md md:h-16 text-sm md:text-xl">
                    Записаться на приём
                </x-button-primary>
            </div>
        </div>
    </div>
</div>
