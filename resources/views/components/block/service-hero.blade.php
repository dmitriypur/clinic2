<div class="relative aspect-[390/600] min-h-[520px] overflow-hidden bg-[#e9eef3] md:aspect-[1920/492] md:min-h-[300px] md:bg-[#f6fbfd]" aria-labelledby="{{ $heroId }}-title">
    @if ($desktopImage)
        <picture class="pointer-events-none absolute inset-0 block h-full w-full" aria-hidden="true">
            @if ($desktopWebp)
                <source media="(min-width: 768px)" type="image/webp" srcset="{{ $desktopWebp }}">
            @endif
            <source media="(min-width: 768px)" srcset="{{ $desktopImage }}">
            @if ($mobileWebp)
                <source type="image/webp" srcset="{{ $mobileWebp }}">
            @endif
            <img src="{{ $mobileImage }}" alt="" class="h-full w-full object-contain object-bottom" loading="eager" fetchpriority="high" decoding="async">
        </picture>
    @endif
    <div class="container relative z-10 flex h-full flex-col items-center pt-16 text-center md:items-start md:justify-center md:pb-[35px] md:pt-0 md:text-left">
        <div class="w-full md:max-w-[50%]">
            <h1 id="{{ $heroId }}-title" class="text-[32px] font-extrabold leading-[0.9] text-[#1f3462] md:max-w-[431px] md:text-[34px] md:font-semibold md:leading-[1.2]">{{ $title }}</h1>
            @if ($description !== '')
                <p class="mt-3 text-base leading-[1.3] text-[#1f3462] md:mt-4 md:max-w-[476px]">{{ $description }}</p>
            @endif
        </div>
        <div class="absolute bottom-8 left-4 right-4 md:static md:mt-5">
            @if ($buttonUrl)
                <a href="{{ $buttonUrl }}" class="inline-block w-full btn-blue-gradient text-center font-semibold text-white !rounded-[10px] !px-[30px] !py-4 text-base leading-[1.22] md:w-auto">{{ $buttonText }}</a>
            @else
                <x-button-blue type="button" @click="openBookingWidgetV3('otpravka-formy')" class="w-full !rounded-[10px] !px-[30px] !py-4 text-base leading-[1.22] md:w-auto">
                    {{ $buttonText }}
                </x-button-blue>
            @endif
        </div>
    </div>
    <span class="sr-only">Имеются противопоказания. Необходима консультация специалиста.</span>
</div>
