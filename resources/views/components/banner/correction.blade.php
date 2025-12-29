<div class="container relative">
    <image-lazy inline-template>
    <div class="absolute top-0 left-0 w-full h-full mx-auto" ref="container">
        <picture>
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('pic')}}' : ''" media="(max-width: 767px)">
            <source :srcset="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''">
            <img :src="isLoaded ? '{{$block->getFirstMediaUrl('bg')}}' : ''" class="w-full h-full object-cover"
                 alt="{{ $block->title }}" width="1280" height="495">
        </picture>
    </div>
    </image-lazy>
    <div class="flex flex-col relative z-10 min-h-[515px] md:min-h-[495px] md:max-w-4xl">
        <!-- Левая часть: текст -->
        <div class="flex flex-col flex-auto h-full w-full py-10 md:py-14 md:pl-12 md:pr-12 text-white">
            <h2 class="text-[34px] md:text-[55px] uppercase tracking-tighter md:tracking-tight font-bold leading-tight mb-4 md:mb-8 [&_span]:font-normal">
                {!! str($block->payload['service_hero_title'])->sanitizeHtml() !!}
            </h2>
            <p class="leading-5 tracking-tighter md:tracking-normal md:text-xl md:max-w-screen-sm mb-8 max-w-[250px] md:max-w-full [&_span]:block md:[&_span]:inline [&_span]:mt-2">
                {!! str($block->payload['service_hero_text'])->sanitizeHtml() !!}
            </p>
            <x-button-blue
                @click="showCallbackModal(null, 'otpravka-formy')"
                class="mt-auto rounded-xl w-full max-w-60 md:max-w-md text-base md:text-xl min-h-10 md:min-h-16">
                Записаться на приём
            </x-button-blue>
        </div>

    </div>
</div>
